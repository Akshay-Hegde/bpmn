<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Test;

use KoolKode\BPMN\Delegate\DelegateTaskRegistry;
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Job\Executor\JobExecutor;
use KoolKode\BPMN\Job\Scheduler\TestJobScheduler;
use KoolKode\BPMN\ManagementService;
use KoolKode\BPMN\Repository\RepositoryService;
use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;
use KoolKode\BPMN\Runtime\RuntimeService;
use KoolKode\BPMN\Task\TaskService;
use KoolKode\Database\Test\DatabaseTestTrait;
use KoolKode\Event\EventDispatcher;
use KoolKode\Expression\ExpressionContextFactory;
use KoolKode\Meta\Info\ReflectionTypeInfo;
use KoolKode\Process\Event\CreateExpressionContextEvent;
use KoolKode\Util\UUID;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Sets up in in-memory Sqlite databse and a process engine using it.
 * 
 * @author Martin Schröder
 */
abstract class BusinessProcessTestCase extends \PHPUnit_Framework_TestCase
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
	 * @var ManagementService
	 */
	protected $managementService;
	
	private $messageHandlers;
	
	private $serviceTaskHandlers;
	
	private $typeInfo;
	
	protected function setUp()
	{
		parent::setUp();
		
		$this->conn = static::createConnection('bpm_');
		
		static::migrateDirectoryUp($this->conn, __DIR__ . '/../../migration');
		
		$logger = NULL;
		
		if($this->isDebug())
		{
			$stderr = fopen('php://stderr', 'wb');
			
			$logger = new Logger('BPMN');
			$logger->pushHandler(new StreamHandler($stderr));
			$logger->pushProcessor(new PsrLogMessageProcessor());
			
			fwrite($stderr, "\n");
			fwrite($stderr, sprintf("TEST CASE: %s\n", $this->getName()));
			
// 			$this->conn$conn->setDebug(true);
// 			$this->conn->setLogger($logger);
		}
		
		$this->messageHandlers = [];
		$this->serviceTaskHandlers = [];
		
		$this->eventDispatcher = new EventDispatcher();
		
		// Provide message handler subscriptions.
		$this->eventDispatcher->connect(function(MessageThrownEvent $event) {
			
			$key = $event->execution->getProcessDefinition()->getKey();
			$id = $event->execution->getActivityId();
			
			if(isset($this->messageHandlers[$key][$id]))
			{
				return $this->messageHandlers[$key][$id]($event);
			}
		});
		
		$this->eventDispatcher->connect(function(TaskExecutedEvent $event) {
			
			$execution = $this->runtimeService->createExecutionQuery()
											  ->executionId($event->execution->getExecutionId())
											  ->findOne();
			
			$key = $execution->getProcessDefinition()->getKey();
			$id = $event->execution->getActivityId();
			
			if(isset($this->serviceTaskHandlers[$key][$id]))
			{
				$this->serviceTaskHandlers[$key][$id]($event->execution);
			}
		});
		
		// Allow for assertions in expressions, e.g. #{ @test.assertEquals(2, processVariable) }
		$this->eventDispatcher->connect(function(CreateExpressionContextEvent $event) {
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
		$this->managementService = $this->processEngine->getManagementService();
		
		if($this->typeInfo === NULL)
		{
			$this->typeInfo = new ReflectionTypeInfo(new \ReflectionClass(get_class($this)));
		}
		
		foreach($this->typeInfo->getMethods() as $method)
		{
			if(!$method->isPublic() || $method->isStatic())
			{
				continue;
			}
			
			foreach($method->getAnnotations() as $anno)
			{
				if($anno instanceof MessageHandler)
				{
					$this->messageHandlers[$anno->processKey][$anno->value] = [$this, $method->getName()];
				}
				
				if($anno instanceof ServiceTaskHandler)
				{
					$this->serviceTaskHandlers[$anno->processKey][$anno->value] = [$this, $method->getName()];
				}
			}
		}
	}
	
	public function isDebug()
	{
		return false;
	}
	
	protected function deployFile($file)
	{
		if(!preg_match("'^(?:(?:[a-z]:)|(/+)|([^:]+://))'i", $file))
		{
			$file = dirname((new \ReflectionClass(get_class($this)))->getFileName()) . DIRECTORY_SEPARATOR . $file;
		}
		
		return $this->repositoryService->deployProcess(new \SplFileInfo($file));
	}
	
	protected function deployDirectory($file, array $extensions = [])
	{
		if(!preg_match("'^(?:(?:[a-z]:)|(/+)|([^:]+://))'i", $file))
		{
			$file = dirname((new \ReflectionClass(get_class($this)))->getFileName()) . DIRECTORY_SEPARATOR . $file;
		}
	
		$builder = $this->repositoryService->createDeployment(pathinfo($file, PATHINFO_FILENAME));
		$builder->addExtensions($extensions);
		$builder->addDirectory($file);
	
		return $this->repositoryService->deploy($builder);
	}
	
	protected function deployArchive($file, array $extensions = [])
	{
		if(!preg_match("'^(?:(?:[a-z]:)|(/+)|([^:]+://))'i", $file))
		{
			$file = dirname((new \ReflectionClass(get_class($this)))->getFileName()) . DIRECTORY_SEPARATOR . $file;
		}
		
		$builder = $this->repositoryService->createDeployment(pathinfo($file, PATHINFO_FILENAME));
		$builder->addExtensions($extensions);
		$builder->addArchive($file);
	
		return $this->repositoryService->deploy($builder);
	}
	
	protected function registerMessageHandler($processDefinitionKey, $nodeId, callable $handler)
	{
		$args = array_slice(func_get_args(), 3);
		
		$this->messageHandlers[(string)$processDefinitionKey][(string)$nodeId] = function($event) use($handler, $args) {
			return call_user_func_array($handler, array_merge([$event], $args));
		};
	}
	
	protected function registerServiceTaskHandler($processDefinitionKey, $activityId, callable $handler)
	{
		$this->serviceTaskHandlers[(string)$processDefinitionKey][(string)$activityId] = $handler;
	}
	
	protected function dumpProcessInstances(UUID $id = NULL)
	{
		$query = $this->runtimeService->createProcessInstanceQuery();
		
		if($id !== NULL)
		{
			$query->processInstanceId($id);
		}
		
		foreach($query->findAll() as $proc)
		{
			echo "\n";
			$this->dumpExecution($this->processEngine->findExecution($proc->getId()));
			echo "\n";
		}
	}
	
	protected function dumpExecution(VirtualExecution $exec)
	{
		$node = $exec->getNode();
		$nodeId = ($node === NULL) ? NULL : $node->getId();
		
		printf("%s%s [ %s ]\n", str_repeat('  ', $exec->getExecutionDepth()), $nodeId, $exec->getId());
	
		foreach($exec->findChildExecutions() as $child)
		{
			$this->dumpExecution($child);
		}
	}
}
