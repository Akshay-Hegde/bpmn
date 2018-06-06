<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace KoolKode\BPMN\Test;

use KoolKode\BPMN\ManagementService;
use KoolKode\BPMN\Delegate\DelegateTaskRegistry;
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\History\HistoricActivityInstance;
use KoolKode\BPMN\History\HistoryService;
use KoolKode\BPMN\Job\Executor\JobExecutor;
use KoolKode\BPMN\Job\Scheduler\TestJobScheduler;
use KoolKode\BPMN\Repository\Deployment;
use KoolKode\BPMN\Repository\RepositoryService;
use KoolKode\BPMN\Runtime\RuntimeService;
use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;
use KoolKode\BPMN\Task\TaskService;
use KoolKode\Database\Test\DatabaseTestTrait;
use KoolKode\Event\EventDispatcher;
use KoolKode\Expression\ExpressionContextFactory;
use KoolKode\Process\Event\CreateExpressionContextEvent;
use KoolKode\Util\UUID;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Sets up in in-memory Sqlite databse and a process engine using it.
 * 
 * @author Martin Schröder
 */
abstract class BusinessProcessTestCase extends TestCase
{
    use DatabaseTestTrait;

    protected $conn;

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var ProcessEngine
     */
    protected $processEngine;

    /**
     * @var JobExecutor
     */
    protected $jobExecutor;

    /**
     * @var DelegateTaskRegistry
     */
    protected $delegateTasks;

    /**
     * @var RepositoryService
     */
    protected $repositoryService;

    /**
     * @var RuntimeService
     */
    protected $runtimeService;

    /**
     * @var TaskService
     */
    protected $taskService;

    /**
     * @var HistoryService
     */
    protected $historyService;

    /**
     * @var ManagementService
     */
    protected $managementService;

    private $messageHandlers;

    private $serviceTaskHandlers;

    protected function setUp()
    {
        parent::setUp();
        
        $this->eventDispatcher = new EventDispatcher();
        
        $this->conn = static::createConnection('bpm_', $this->eventDispatcher);
        
        static::migrateDirectoryUp($this->conn, __DIR__ . '/../../migration');
        
        $logger = null;
        
        if (null !== ($logLevel = $this->getLogLevel())) {
            $stderr = \fopen('php://stderr', 'wb');
            
            $levels = \array_change_key_case(Logger::getLevels(), \CASE_UPPER);
            
            $logger = new Logger('BPMN');
            $logger->pushHandler(new StreamHandler($stderr, $levels[\strtoupper($logLevel)]));
            $logger->pushProcessor(new PsrLogMessageProcessor());
            
            \fwrite($stderr, "\n");
            \fwrite($stderr, \sprintf("TEST CASE: %s\n", $this->getName()));
            
            //             $this->conn->setDebug(true);
            //             $this->conn->setLogger($logger);
        }
        
        $this->messageHandlers = [];
        $this->serviceTaskHandlers = [];
        
        // Provide message handler subscriptions.
        $this->eventDispatcher->connect(function (MessageThrownEvent $event) {
            $def = $this->repositoryService->createProcessDefinitionQuery()->processDefinitionId($event->execution->getProcessDefinitionId())->findOne();
            
            $key = $def->getKey();
            $id = $event->execution->getActivityId();
            
            if (isset($this->messageHandlers[$key][$id])) {
                return $this->messageHandlers[$key][$id]->execute($event);
            }
            
            if (isset($this->messageHandlers['*'][$id])) {
                return $this->messageHandlers['*'][$id]->execute($event);
            }
        });
        
        $this->eventDispatcher->connect(function (TaskExecutedEvent $event) {
            $execution = $this->runtimeService->createExecutionQuery()->executionId($event->execution->getExecutionId())->findOne();
            
            $key = $execution->getProcessDefinition()->getKey();
            $id = $event->execution->getActivityId();
            
            if (isset($this->serviceTaskHandlers[$key][$id])) {
                $this->serviceTaskHandlers[$key][$id]->execute($event->execution);
            }
            
            if (isset($this->serviceTaskHandlers['*'][$id])) {
                $this->serviceTaskHandlers['*'][$id]->execute($event->execution);
            }
        });
        
        // Allow for assertions in expressions, e.g. #{ @test.assertEquals(2, processVariable) }
        $this->eventDispatcher->connect(function (CreateExpressionContextEvent $event) {
            $event->access->setVariable('@test', $this);
        });
        
        $this->delegateTasks = new DelegateTaskRegistry();
        
        $this->processEngine = new ProcessEngine($this->conn, $this->eventDispatcher, new ExpressionContextFactory());
        $this->processEngine->setDelegateTaskFactory($this->delegateTasks);
        $this->processEngine->setLogger($logger);
        
        $scheduler = new TestJobScheduler($this->processEngine);
        $this->jobExecutor = new JobExecutor($this->processEngine, $scheduler);
        
        $this->processEngine->setJobExecutor($this->jobExecutor);
        
        $this->repositoryService = $this->processEngine->getRepositoryService();
        $this->runtimeService = $this->processEngine->getRuntimeService();
        $this->taskService = $this->processEngine->getTaskService();
        $this->historyService = $this->processEngine->getHistoryService();
        $this->managementService = $this->processEngine->getManagementService();
        
        $ref = new \ReflectionClass(static::class);
        
        foreach ($ref->getMethods() as $method) {
            if ($method->isStatic() || !$method->hasReturnType()) {
                continue;
            }
            
            switch ($method->getReturnType()) {
                case MessageHandler::class:
                    $method->setAccessible(true);
                    $handler = $method->invoke($this);
                    
                    $this->messageHandlers[$handler->getProcessKey() ?? '*'][$handler->getMessageName()] = $handler;
                    break;
                case ServiceTaskHandler::class:
                    $method->setAccessible(true);
                    $handler = $method->invoke($this);
                    
                    $this->serviceTaskHandlers[$handler->getProcessKey() ?? '*'][$handler->getServiceTask()] = $handler;
                    break;
            }
        }
    }

    public function getRepositoryService(): RepositoryService
    {
        return $this->repositoryService;
    }

    public function getRuntimeService(): RuntimeService
    {
        return $this->runtimeService;
    }

    public function getTaskService(): TaskService
    {
        return $this->taskService;
    }

    public function getHistoryService(): HistoryService
    {
        return $this->historyService;
    }

    public function getManagementService(): ManagementService
    {
        return $this->managementService;
    }

    /**
     * Get the minimum level of log messages to be displayed.
     * 
     * Logging is not enabled by default.
     */
    public function getlogLevel(): ?string
    {
        return null;
    }

    protected function deployFile(string $file): Deployment
    {
        if (!\preg_match("'^(?:(?:[a-z]:)|(/+)|([^:]+://))'i", $file)) {
            $file = \dirname((new \ReflectionClass(\get_class($this)))->getFileName()) . \DIRECTORY_SEPARATOR . $file;
        }
        
        return $this->repositoryService->deployProcess(new \SplFileInfo($file));
    }

    protected function deployDirectory(string $file, array $extensions = []): Deployment
    {
        if (!\preg_match("'^(?:(?:[a-z]:)|(/+)|([^:]+://))'i", $file)) {
            $file = \dirname((new \ReflectionClass(\get_class($this)))->getFileName()) . \DIRECTORY_SEPARATOR . $file;
        }
        
        $builder = $this->repositoryService->createDeployment(\pathinfo($file, \PATHINFO_FILENAME));
        $builder->addExtensions($extensions);
        $builder->addDirectory($file);
        
        return $this->repositoryService->deploy($builder);
    }

    protected function deployArchive(string $file, array $extensions = []): Deployment
    {
        if (!\preg_match("'^(?:(?:[a-z]:)|(/+)|([^:]+://))'i", $file)) {
            $file = \dirname((new \ReflectionClass(\get_class($this)))->getFileName()) . \DIRECTORY_SEPARATOR . $file;
        }
        
        $builder = $this->repositoryService->createDeployment(\pathinfo($file, \PATHINFO_FILENAME));
        $builder->addExtensions($extensions);
        $builder->addArchive($file);
        
        return $this->repositoryService->deploy($builder);
    }

    protected function registerMessageHandler(?string $processDefinitionKey, string $nodeId, callable $callback): void
    {
        $this->messageHandlers[$processDefinitionKey ?? '*'][$nodeId] = new MessageHandler($nodeId, $processDefinitionKey, $callback);
    }

    protected function registerServiceTaskHandler(?string $processDefinitionKey, string $activityId, callable $callback): void
    {
        $this->serviceTaskHandlers[$processDefinitionKey ?? '*'][$activityId] = new ServiceTaskHandler($activityId, $processDefinitionKey, $callback);
    }

    protected function dumpProcessInstances(?UUID $id = null): void
    {
        $query = $this->runtimeService->createProcessInstanceQuery();
        
        if ($id !== null) {
            $query->processInstanceId($id);
        }
        
        foreach ($query->findAll() as $proc) {
            echo "\n";
            $this->dumpExecution($this->processEngine->findExecution($proc->getId()));
            echo "\n";
        }
    }

    protected function dumpExecution(VirtualExecution $exec): void
    {
        $node = $exec->getNode();
        $nodeId = ($node === null) ? null : $node->getId();
        
        \printf("%s%s [ %s ]\n", \str_repeat('  ', $exec->getExecutionDepth()), $nodeId, $exec->getId());
        
        foreach ($exec->findChildExecutions() as $child) {
            $this->dumpExecution($child);
        }
    }

    protected function findCompletedActivityDefinitionKeys(?UUID $processId = null): array
    {
        $query = $this->historyService->createHistoricActivityInstanceQuery()->completed(true)->orderByStartedAt();
        
        if ($processId !== null) {
            $query->processInstanceId($processId);
        }
        
        return \array_map(function (HistoricActivityInstance $activity) {
            return $activity->getDefinitionKey();
        }, $query->findAll());
    }
}
