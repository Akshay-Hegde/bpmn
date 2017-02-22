<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

use KoolKode\BPMN\Delegate\DelegateTaskFactoryInterface;
use KoolKode\BPMN\History\Event\ExecutionCreatedEvent;
use KoolKode\BPMN\History\Event\ExecutionModifiedEvent;
use KoolKode\BPMN\History\Event\ExecutionTerminatedEvent;
use KoolKode\BPMN\History\HistoryService;
use KoolKode\BPMN\Job\Executor\JobExecutorInterface;
use KoolKode\BPMN\Job\Handler\AsyncCommandHandler;
use KoolKode\BPMN\Job\Job;
use KoolKode\BPMN\ManagementService;
use KoolKode\BPMN\Repository\RepositoryService;
use KoolKode\BPMN\Runtime\RuntimeService;
use KoolKode\BPMN\Task\TaskService;
use KoolKode\Database\ConnectionInterface;
use KoolKode\Database\DB;
use KoolKode\Database\ParamEncoderDecorator;
use KoolKode\Database\StatementInterface;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Event\EventDispatcherInterface;
use KoolKode\Expression\ExpressionContextFactoryInterface;
use KoolKode\Process\AbstractEngine;
use KoolKode\Process\Command\VoidCommand;
use KoolKode\Process\Execution;
use KoolKode\Process\Node;
use KoolKode\Process\Transition;
use KoolKode\Util\UnicodeString;
use KoolKode\Util\UUID;

/**
 * BPMN 2.0 process engine backed by a relational database.
 * 
 * @author Martin Schröder
 */
class ProcessEngine extends AbstractEngine implements ProcessEngineInterface
{
    /**
     * Database connection.
     * 
     * @var ConnectionInterface
     */
    protected $conn;

    protected $handleTransactions;

    protected $interceptors = [];

    protected $jobExecutor;

    protected $delegateTaskFactory;

    protected $repositoryService;

    protected $runtimeService;

    protected $taskService;

    protected $managementService;

    protected $historyService;

    public function __construct(ConnectionInterface $conn, EventDispatcherInterface $dispatcher, ExpressionContextFactoryInterface $factory, $handleTransactions = true)
    {
        parent::__construct($dispatcher, $factory);
        
        $decorator = new ParamEncoderDecorator();
        $decorator->registerParamEncoder(new BinaryDataParamEncoder());
        
        // Clone DB connection as it is being modified.
        $conn = clone $conn;
        $conn->addDecorator($decorator);
        
        $this->conn = $conn;
        $this->handleTransactions = $handleTransactions ? true : false;
        
        $this->repositoryService = new RepositoryService($this);
        $this->runtimeService = new RuntimeService($this);
        $this->taskService = new TaskService($this);
        $this->managementService = new ManagementService($this);
        $this->historyService = new HistoryService($this);
        
        $this->eventDispatcher->connect([
            $this->historyService,
            'recordEvent'
        ]);
    }

    public function __debugInfo()
    {
        return [
            'conn' => $this->conn,
            'transactional' => $this->handleTransactions,
            'executionDepth' => $this->executionDepth,
            'executionCount' => $this->executionCount,
            'executions' => array_values($this->executions)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryService()
    {
        return $this->repositoryService;
    }

    /**
     * {@inheritdoc}
     */
    public function getRuntimeService()
    {
        return $this->runtimeService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTaskService()
    {
        return $this->taskService;
    }

    /**
     * {@inheritdoc}
     */
    public function getHistoryService()
    {
        return $this->historyService;
    }

    /**
     * {@inheritdoc}
     */
    public function getManagementService()
    {
        return $this->managementService;
    }

    /**
     * Create a prepared statement from the given SQL.
     * 
     * @param string $sql
     * @return StatementInterface
     */
    public function prepareQuery($sql)
    {
        return $this->conn->prepare($sql);
    }

    /**
     * Get the DB connection being used by the engine.
     * 
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->conn;
    }

    public function setDelegateTaskFactory(DelegateTaskFactoryInterface $factory = null)
    {
        $this->delegateTaskFactory = $factory;
    }

    public function createDelegateTask($typeName)
    {
        if ($this->delegateTaskFactory === null) {
            throw new \RuntimeException('Process engine cannot delegate tasks without a delegate task factory');
        }
        
        return $this->delegateTaskFactory->createDelegateTask($typeName);
    }

    /**
     * @return JobExecutorInterface
     */
    public function getJobExecutor()
    {
        return $this->jobExecutor;
    }

    public function setJobExecutor(JobExecutorInterface $executor)
    {
        $this->jobExecutor = $executor;
    }

    public function registerExecutionInterceptor(ExecutionInterceptorInterface $interceptor)
    {
        return $this->interceptors[] = $interceptor;
    }

    public function unregisterExecutionInterceptor(ExecutionInterceptorInterface $interceptor)
    {
        if (false !== ($index = array_search($interceptor, $this->interceptors, true))) {
            unset($this->interceptors[$index]);
        }
        
        return $interceptor;
    }

    /**
     * Schedule a job for execution.
     * 
     * This method will not cause an error in case of a missing job executor!
     * 
     * @param UUID $executionId Target execution being used by the job.
     * @param string $handlerType The name of the job handler.
     * @param mixed $data Arbitrary data to be passed to the job handler.
     * @param \DateTimeInterface $runAt Scheduled execution time, a value of null schedules the job for immediate execution.
     * @return Job The persisted job instance or null when no job executor has been configured.
     */
    public function scheduleJob(UUID $executionId, $handlerType, $data, \DateTimeInterface $runAt = null)
    {
        if ($this->jobExecutor !== null) {
            return $this->jobExecutor->scheduleJob($executionId, $handlerType, $data, $runAt);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function performExecution(callable $callback)
    {
        $trans = false;
        
        if ($this->executionDepth == 0) {
            if ($this->handleTransactions) {
                $this->debug('BEGIN transaction');
                $this->conn->beginTransaction();
            }
            
            $trans = true;
        }
        
        try {
            $chain = new ExecutionInterceptorChain(function () use ($callback) {
                return parent::performExecution($callback);
            }, $this->executionDepth, $this->interceptors);
            
            $result = $chain->performExecution($this->executionDepth);
            
            if ($trans) {
                if ($this->handleTransactions) {
                    $this->debug('COMMIT transaction');
                    $this->conn->commit();
                }
            }
        } catch (\Exception $e) {
            if ($trans) {
                if ($this->handleTransactions) {
                    $this->debug('ROLL BACK transaction');
                    $this->conn->rollBack();
                }
            }
            
            throw $e;
        } finally {
            if ($trans) {
                $this->executions = [];
                $this->jobs = [];
            }
        }
        
        // Schedule jobs after commit to DB, ensures consistent state in DB and failed scheduling attempts can be repeated.
        if ($trans) {
            if ($this->jobExecutor !== null) {
                $this->jobExecutor->syncScheduledJobs();
            }
        }
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createExecuteNodeCommand(Execution $execution, Node $node)
    {
        $command = parent::createExecuteNodeCommand($execution, $node);
        $behavior = $node->getBehavior();
        
        if ($behavior instanceof AbstractActivity && $behavior->isAsyncBefore()) {
            if ($this->jobExecutor !== null && $this->jobExecutor->hasJobScheduler() && $this->jobExecutor->hasJobHandler(AsyncCommandHandler::HANDLER_TYPE)) {
                $this->scheduleJob($execution->getId(), AsyncCommandHandler::HANDLER_TYPE, [
                    AsyncCommandHandler::PARAM_COMMAND => $command,
                    AsyncCommandHandler::PARAM_NODE_ID => $node->getId()
                ]);
                
                // Move execution out of any previous node before proceeding.
                $execution->setNode(null);
                
                // return No-op command instead of execute node command.
                return new VoidCommand();
            }
            
            $this->warning('Behavior of {node} should be executed via "{handler}" job within {execution}', [
                'node' => (string) $node,
                'handler' => AsyncCommandHandler::HANDLER_TYPE,
                'execution' => (string) $execution
            ]);
        }
        
        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function createTakeTransitionCommand(Execution $execution, Transition $transition = null)
    {
        $command = parent::createTakeTransitionCommand($execution, $transition);
        $node = $execution->getNode();
        
        if ($node !== null) {
            $behavior = $node->getBehavior();
            
            if ($behavior instanceof AbstractActivity && $behavior->isAsyncAfter()) {
                if ($this->jobExecutor !== null && $this->jobExecutor->hasJobScheduler() && $this->jobExecutor->hasJobHandler(AsyncCommandHandler::HANDLER_TYPE)) {
                    $this->scheduleJob($execution->getId(), AsyncCommandHandler::HANDLER_TYPE, [
                        AsyncCommandHandler::PARAM_COMMAND => $command,
                        AsyncCommandHandler::PARAM_NODE_ID => $node->getId()
                    ]);
                    
                    // Move execution out of any previous node before proceeding.
                    $execution->setNode(null);
                    
                    // return No-op command instead of execute node command.
                    return new VoidCommand();
                }
                
                $this->warning('Behavior of {node} should be executed via "{handler}" job within {execution}', [
                    'node' => (string) $node,
                    'handler' => AsyncCommandHandler::HANDLER_TYPE,
                    'execution' => (string) $execution
                ]);
            }
        }
        
        return $command;
    }

    /**
     * {@inheritdoc}
     */
    public function findExecution(UUID $id)
    {
        static $injectVars = null;
        
        if ($injectVars === null) {
            $injectVars = new \ReflectionMethod(VirtualExecution::class, 'injectVariablesLocal');
            $injectVars->setAccessible(true);
        }
        
        $ref = (string) $id;
        
        if (isset($this->executions[$ref])) {
            return $this->executions[$ref];
        }
        
        // Need to select multiple rows as one process instance may span over different process definitions (using CallActivity)
        $sql = "
            SELECT DISTINCT e.`process_id`, d.`id` as definition_id, d.`definition`
            FROM `#__bpmn_execution` AS e
            INNER JOIN `#__bpmn_process_definition` AS d ON (d.`id` = e.`definition_id`)
            WHERE e.`process_id` = (
                SELECT `process_id` FROM `#__bpmn_execution` WHERE `id` = :eid
            )
        ";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue('eid', $id);
        $stmt->transform('process_id', new UUIDTransformer());
        $stmt->transform('definition_id', new UUIDTransformer());
        $stmt->execute();
        
        $rows = $stmt->fetchRows();
        
        if (empty($rows)) {
            throw new \OutOfBoundsException(sprintf('Execution not found: "%s"', $ref));
        }
        
        $processId = $rows[0]['process_id'];
        $definitions = [];
        
        foreach ($rows as $row) {
            $definitions[(string) $row['definition_id']] = unserialize(BinaryData::decode($row['definition']));
        }
        
        // Select (and lock) all execution rows of the process instance using a pessimistic lock.
        $sql = "
            SELECT e.*
            FROM `#__bpmn_execution` AS e
            WHERE e.`process_id` = :pid
        ";
        
        switch ($this->conn->getDriverName()) {
            case DB::DRIVER_MYSQL:
            case DB::DRIVER_POSTGRESQL:
                $sql .= " FOR UPDATE";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindAll([
            'pid' => $processId
        ]);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->transform('pid', new UUIDTransformer());
        $stmt->transform('process_id', new UUIDTransformer());
        $stmt->transform('definition_id', new UUIDTransformer());
        $stmt->execute();
        
        $variables = [];
        $executions = [];
        $parents = [];
        
        while ($row = $stmt->fetchNextRow()) {
            $id = $row['id'];
            $pid = $row['pid'];
            $defId = (string) $row['definition_id'];
            
            if (empty($definitions[$defId])) {
                throw new \OutOfBoundsException(sprintf('Missing process definition "%s" referenced from execution "%s"', $defId, $id));
            }
            
            $definition = $definitions[$defId];
            
            if ($pid !== null) {
                $parents[(string) $id] = (string) $pid;
            }
            
            $state = (int) $row['state'];
            $active = (float) $row['active'];
            $node = ($row['node'] === null) ? null : $definition->findNode($row['node']);
            $transition = ($row['transition'] === null) ? null : $definition->findTransition($row['transition']);
            $businessKey = $row['business_key'];
            
            $variables[(string) $id] = [];
            
            $exec = $executions[(string) $id] = new VirtualExecution($id, $this, $definition);
            $exec->setBusinessKey($businessKey);
            $exec->setExecutionState($state);
            $exec->setNode($node);
            $exec->setTransition($transition);
            $exec->setTimestamp($active);
        }
        
        foreach ($parents as $id => $pid) {
            $executions[$id]->setParentExecution($executions[$pid]);
        }
        
        if (!empty($variables)) {
            $params = [];
            
            foreach (array_keys($variables) as $i => $k) {
                $params['p' . $i] = new UUID($k);
            }
            
            $placeholders = implode(', ', array_map(function ($p) {
                return ':' . $p;
            }, array_keys($params)));
            
            $sql = "
                SELECT `execution_id`, `name`, `value_blob`
                FROM `#__bpmn_execution_variables`
                WHERE `execution_id` IN ($placeholders)
            ";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindAll($params);
            $stmt->transform('execution_id', new UUIDTransformer());
            $stmt->execute();
            
            while (false !== ($row = $stmt->fetchNextRow())) {
                $variables[(string) $row['execution_id']][$row['name']] = unserialize(BinaryData::decode($row['value_blob']));
            }
        }
        
        foreach ($variables as $id => $vars) {
            $injectVars->invoke($executions[$id], $vars);
        }
        
        foreach ($executions as $execution) {
            $execution->setSyncState(Execution::SYNC_STATE_NO_CHANGE);
            
            $this->executions[(string) $execution->getId()] = $execution;
        }
        
        if (empty($this->executions[$ref])) {
            throw new \OutOfBoundsException(sprintf('Execution not found: "%s"', $ref));
        }
        
        return $this->executions[$ref];
    }

    /**
     * {@inheritdoc}
     */
    protected function syncNewExecution(Execution $execution, array $syncData)
    {
        $this->conn->insert('#__bpmn_execution', [
            'id' => $syncData['id'],
            'pid' => $syncData['parentId'],
            'process_id' => $syncData['processId'],
            'definition_id' => $syncData['modelId'],
            'state' => $syncData['state'],
            'active' => $syncData['timestamp'],
            'node' => $syncData['node'],
            'transition' => $syncData['transition'],
            'depth' => $syncData['depth'],
            'business_key' => $syncData['businessKey']
        ]);
        
        $this->syncVariables($execution, $syncData);
        
        $vars = empty($syncData['variables']) ? [] : $syncData['variables'];
        
        $this->notify(new ExecutionCreatedEvent($execution, $vars, $this));
        
        return parent::syncNewExecution($execution, $syncData);
    }

    /**
     * {@inheritdoc}
     */
    protected function syncModifiedExecution(Execution $execution, array $syncData)
    {
        $this->conn->update('#__bpmn_execution', [
            'id' => $syncData['id']
        ], [
            'pid' => $syncData['parentId'],
            'process_id' => $syncData['processId'],
            'state' => $syncData['state'],
            'active' => $syncData['timestamp'],
            'node' => $syncData['node'],
            'depth' => $syncData['depth'],
            'transition' => $syncData['transition'],
            'business_key' => $syncData['businessKey']
        ]);
        
        $this->syncVariables($execution, $syncData);
        
        $vars = empty($syncData['variables']) ? [] : $syncData['variables'];
        
        $this->notify(new ExecutionModifiedEvent($execution, $vars, $this));
        
        return parent::syncModifiedExecution($execution, $syncData);
    }

    /**
     * {@inheritdoc}
     */
    protected function syncRemovedExecution(Execution $execution)
    {
        foreach ($execution->findChildExecutions() as $child) {
            $this->syncRemovedExecution($child);
        }
        
        $this->conn->delete('#__bpmn_execution', [
            'id' => $execution->getId()
        ]);
        
        $this->notify(new ExecutionTerminatedEvent($execution, $this));
        
        return parent::syncRemovedExecution($execution);
    }

    protected function syncVariables(Execution $execution, array $syncData)
    {
        $delta = $this->computeVarDelta($execution, $syncData);
        
        if (!empty($delta[Execution::SYNC_STATE_REMOVED])) {
            $this->conn->delete('#__bpmn_execution_variables', [
                'execution_id' => $execution->getId(),
                'name' => (array) $delta[Execution::SYNC_STATE_REMOVED]
            ]);
        }
        
        if (!empty($delta[Execution::SYNC_STATE_MODIFIED])) {
            foreach ($delta[Execution::SYNC_STATE_MODIFIED] as $k) {
                $value = null;
                
                if (is_scalar($syncData['variables'][$k])) {
                    $val = $syncData['variables'][$k];
                    
                    if (is_bool($val)) {
                        $val = $val ? '1' : '0';
                    }
                    
                    $value = new UnicodeString(trim($val));
                    
                    if ($value->length() > 250) {
                        $value = $value->substring(0, 250);
                    }
                    
                    $value = $value->toLowerCase();
                }
                
                $this->conn->insert('#__bpmn_execution_variables', [
                    'execution_id' => $execution->getId(),
                    'name' => $k,
                    'value' => $value,
                    'value_blob' => new BinaryData(serialize($syncData['variables'][$k]))
                ]);
            }
        }
    }

    protected function computeVarDelta(Execution $execution, array $syncData)
    {
        $result = [
            0 => [],
            1 => []
        ];
        
        $data = $execution->getSyncData();
        
        $vars = empty($data['variables']) ? [] : $data['variables'];
        $syncData = empty($syncData['variables']) ? [] : $syncData['variables'];
        
        foreach ($vars as $k => $v) {
            if (!array_key_exists($k, $syncData)) {
                $result[0][$k] = true;
                continue;
            }
            
            if ($v !== $syncData[$k]) {
                $result[1][$k] = true;
            }
            
            unset($syncData[$k]);
        }
        
        foreach ($syncData as $k => $v) {
            $result[1][$k] = true;
        }
        
        return [
            Execution::SYNC_STATE_REMOVED => array_unique(array_keys(array_merge($result[0], $result[1]))),
            Execution::SYNC_STATE_MODIFIED => array_keys($result[1])
        ];
        
        return array_map('array_keys', $result);
    }
}
