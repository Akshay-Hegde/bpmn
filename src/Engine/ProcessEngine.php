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
use KoolKode\BPMN\Job\Job;
use KoolKode\BPMN\Repository\RepositoryService;
use KoolKode\BPMN\Runtime\RuntimeService;
use KoolKode\BPMN\Task\TaskService;
use KoolKode\Database\ConnectionInterface;
use KoolKode\Database\ParamEncoderDecorator;
use KoolKode\Database\StatementInterface;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Event\EventDispatcherInterface;
use KoolKode\Expression\ExpressionContextFactoryInterface;
use KoolKode\Process\AbstractEngine;
use KoolKode\Process\Execution;
use KoolKode\Util\UnicodeString;
use KoolKode\Util\UUID;

/**
 * BPMN 2.0 process engine backed by a relational database.
 * 
 * @author Martin Schröder
 */
class ProcessEngine extends AbstractEngine implements ProcessEngineInterface
{
	const SUB_FLAG_SIGNAL = 1;
	const SUB_FLAG_MESSAGE = 2;
	
	protected $conn;
	
	protected $handleTransactions;
	
	protected $interceptors = [];
	
	protected $jobs = [];
	
	protected $jobExecutor;
	
	protected $delegateTaskFactory;
	
	protected $repositoryService;
	
	protected $runtimeService;
	
	protected $taskService;
	
	public function __construct(ConnectionInterface $conn, EventDispatcherInterface $dispatcher, ExpressionContextFactoryInterface $factory, $handleTransactions = true)
	{
		parent::__construct($dispatcher, $factory);
		
		$conn = new ParamEncoderDecorator($conn);
		$conn->registerParamEncoder(new BinaryDataParamEncoder());
		
		$this->conn = $conn;
		$this->handleTransactions = $handleTransactions ? true : false;
		
		$this->repositoryService = new RepositoryService($this);
		$this->runtimeService = new RuntimeService($this);
		$this->taskService = new TaskService($this);
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
	
	public function getRepositoryService()
	{
		return $this->repositoryService;
	}
	
	public function getRuntimeService()
	{
		return $this->runtimeService;
	}
	
	public function getTaskService()
	{
		return $this->taskService;
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
	 * get the last ID that has been inserted / next sequence value.
	 * 
	 * @param string $seq Name of the sequence to be used.
	 * @return integer
	 */
	public function getLastInsertId($seq = NULL)
	{
		return $this->conn->lastInsertId($seq);
	}
	
	public function setDelegateTaskFactory(DelegateTaskFactoryInterface $factory = NULL)
	{
		$this->delegateTaskFactory = $factory;
	}
	
	public function createDelegateTask($typeName)
	{
		if($this->delegateTaskFactory === NULL)
		{
			throw new \RuntimeException('Process engine cannot delegate tasks without a delegate task factory');
		}
		
		return $this->delegateTaskFactory->createDelegateTask($typeName);
	}
	
	public function registerExecutionInterceptor(ExecutionInterceptorInterface $interceptor)
	{
		return $this->interceptors[] = $interceptor;
	}
	
	public function unregisterExecutionInterceptor(ExecutionInterceptorInterface $interceptor)
	{
		if(false !== ($index = array_search($interceptor, $this->interceptors, true)))
		{
			unset($this->interceptors[$index]);
		}
		
		return $interceptor;
	}
	
	public function scheduleJob(VirtualExecution $execution, $handlerType, $data)
	{
		$id = UUID::createRandom();
		$executionId = $execution->getId();
		$handlerType = (string)$handlerType;
		
		return $this->jobs[] = new Job($id, $executionId, $handlerType, $data);
	}
	
	protected function performExecution(callable $callback)
	{
		$trans = false;
		
		if($this->executionDepth == 0)
		{
			if($this->handleTransactions)
			{
				$this->debug('BEGIN transaction');
				$this->conn->beginTransaction();
			}
			
			$trans = true;
		}
		
		try
		{
			$chain = new ExecutionInterceptorChain(function() use($callback) {
				return parent::performExecution($callback);
			}, $this->executionDepth, $this->interceptors);
			
			$result = $chain->performExecution($this->executionDepth);
			
			if($trans)
			{
				$pendingJobs = $this->jobs;

				foreach($pendingJobs as $job)
				{
					$this->saveJob($job);
				}
				
				if($this->handleTransactions)
				{
					$this->debug('COMMIT transaction');
					$this->conn->commit();
				}
				
				$this->executions = [];
				$this->jobs = [];
				
				// Schedule jobs after outermost transaction has been completed successfully.
				foreach($pendingJobs as $job)
				{
					$this->jobExecutor->scheduleJob($job);
				}
			}
			
			return $result;
		}
		catch(\Exception $e)
		{
			if($trans)
			{
				if($this->handleTransactions)
				{
					$this->debug('ROLL BACK transaction');
					$this->conn->rollBack();
				}
			}
			
			throw $e;
		}
		finally
		{
			if($trans)
			{
				$this->executions = [];
				$this->jobs = [];
			}
		}
	}
	
	protected function saveJob(Job $job)
	{
		$stmt = $this->conn->prepare("
			INSERT INTO `#__bpmn_job`
				(`id`, `execution_id`, `handler_type`, `handler_data`)
			VALUES
				(:id, :eid, :type, :data)
		");
		$stmt->bindValue('id', $job->getId());
		$stmt->bindValue('eid', $job->getExecutionId());
		$stmt->bindValue('type', $job->getHandlerType());
		$stmt->bindValue('data', new BinaryData(serialize($job->getHandlerData())));
		$stmt->execute();
	}
	
	public function findExecution(UUID $id)
	{
		$ref = (string)$id;
		
		if(isset($this->executions[$ref]))
		{
			return $this->executions[$ref];
		}
		
		$sql = "	SELECT e.*, d.`definition`
					FROM `#__bpmn_execution` AS e
					INNER JOIN `#__bpmn_process_definition` AS d ON (d.`id` = e.`definition_id`)
					WHERE e.`process_id` IN (
						SELECT `process_id`
						FROM `#__bpmn_execution`
						WHERE `id` = :eid
					)
		";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindAll(['eid' => $id]);
		$stmt->transform('id', new UUIDTransformer());
		$stmt->transform('pid', new UUIDTransformer());
		$stmt->transform('process_id', new UUIDTransformer());
		$stmt->transform('definition_id', new UUIDTransformer());
		$stmt->execute();
		
		$variables = [];
		$executions = [];
		$parents = [];
		$defs = [];
		
		while($row = $stmt->fetchNextRow())
		{
			$id = $row['id'];
			$pid = $row['pid'];
			$defId = (string)$row['definition_id'];
			
			if($pid !== NULL)
			{
				$parents[(string)$id] = (string)$pid;
			}
			
			if(isset($defs[$defId]))
			{
				$definition = $defs[$defId];
			}
			else
			{
				$definition = $defs[$defId] = unserialize(BinaryData::decode($row['definition']));
			}
			
			$state = (int)$row['state'];
			$active = (float)$row['active'];
			$node = ($row['node'] === NULL) ? NULL : $definition->findNode($row['node']);
			$transition = ($row['transition'] === NULL) ? NULL : $definition->findTransition($row['transition']);
			$businessKey = $row['business_key'];
			
			$variables[(string)$id] = [];
			
			$exec = $executions[(string)$id] = new VirtualExecution($id, $this, $definition);
			$exec->setBusinessKey($businessKey);
			$exec->setExecutionState($state);
			$exec->setNode($node);
			$exec->setTransition($transition);
			$exec->setTimestamp($active);
		}
		
		foreach($parents as $id => $pid)
		{
			$executions[$id]->setParentExecution($executions[$pid]);
		}
		
		if(!empty($variables))
		{
			$params = [];
			
			foreach(array_keys($variables) as $i => $k)
			{
				$params['p' . $i] = new UUID($k);
			}
			
			$placeholders = implode(', ', array_map(function($p) {
				return ':' . $p;
			}, array_keys($params)));
			
			$sql = "	SELECT `execution_id`, `name`, `value_blob`
						FROM `#__bpmn_execution_variables`
						WHERE `execution_id` IN ($placeholders)
			";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindAll($params);
			$stmt->transform('execution_id', new UUIDTransformer());
			$stmt->execute();
			
			while(false !== ($row = $stmt->fetchNextRow()))
			{
				$variables[(string)$row['execution_id']][$row['name']] = unserialize(BinaryData::decode($row['value_blob']));
			}
		}
		
		foreach($variables as $id => $vars)
		{
			$executions[$id]->setVariablesLocal($vars);
		}
		
		foreach($executions as $execution)
		{
			$execution->setSyncState(Execution::SYNC_STATE_NO_CHANGE);
			
			$this->executions[(string)$execution->getId()] = $execution;
		}
		
		if(empty($this->executions[$ref]))
		{
			throw new \OutOfBoundsException(sprintf('Execution not found: "%s"', $ref));
		}
		
		return $this->executions[$ref];
	}
	
	protected function syncNewExecution(Execution $execution, array $syncData)
	{
		$sql = "	INSERT INTO `#__bpmn_execution` (
						`id`, `pid`, `process_id`, `definition_id`, `state`, `active`,
						`node`, `transition`, `depth`, `business_key`
					) VALUES (
						:id, :parentId, :processId, :modelId, :state, :timestamp,
						:node, :transition, :depth, :businessKey
					)
		";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue('id', $syncData['id']);
		$stmt->bindValue('parentId', $syncData['parentId']);
		$stmt->bindValue('processId', $syncData['processId']);
		$stmt->bindValue('modelId', $syncData['modelId']);
		$stmt->bindValue('state', $syncData['state']);
		$stmt->bindValue('timestamp', $syncData['timestamp']);
		$stmt->bindValue('node', $syncData['node']);
		$stmt->bindValue('transition', $syncData['transition']);
		$stmt->bindValue('depth', $syncData['depth']);
		$stmt->bindValue('businessKey', $syncData['businessKey']);
		
		$stmt->execute();
		
		$this->syncVariables($execution, $syncData);
		
		return parent::syncNewExecution($execution, $syncData);
	}
	
	protected function syncModifiedExecution(Execution $execution, array $syncData)
	{
		$sql = "	UPDATE `#__bpmn_execution`
					SET `pid` = :pid,
						`process_id` = :process,
						`state` = :state,
						`active` = :timestamp,
						`node` = :node,
						`depth` = :depth,
						`transition` = :transition,
						`business_key` = :bkey
					WHERE `id` = :id
		";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue('id', $syncData['id']);
		$stmt->bindValue('pid', $syncData['parentId']);
		$stmt->bindValue('process', $syncData['processId']);
		$stmt->bindValue('state', $syncData['state']);
		$stmt->bindValue('timestamp', $syncData['timestamp']);
		$stmt->bindValue('node', $syncData['node']);
		$stmt->bindValue('transition', $syncData['transition']);
		$stmt->bindValue('depth', $syncData['depth']);
		$stmt->bindValue('bkey', $syncData['businessKey']);
		$stmt->execute();
		
		$this->syncVariables($execution, $syncData);
		
		return parent::syncModifiedExecution($execution, $syncData);
	}
	
	protected function syncRemovedExecution(Execution $execution)
	{
		foreach($execution->findChildExecutions() as $child)
		{
			$this->syncRemovedExecution($child);
		}
		
		$sql = "	DELETE FROM `#__bpmn_execution`
					WHERE `id` = :id
		";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindValue('id', $execution->getId());
		$stmt->execute();
		
		return parent::syncRemovedExecution($execution);
	}
	
	protected function syncVariables(Execution $execution, array $syncData)
	{
		$delta = $this->computeVarDelta($execution, $syncData);
		
		if(!empty($delta[Execution::SYNC_STATE_REMOVED]))
		{
			$params = [];
		
			foreach($delta[Execution::SYNC_STATE_REMOVED] as $k)
			{
				$params['n' . count($params)] = $k;
			}
		
			$placeholders = implode(', ', array_map(function($p) {
				return ':' . $p;
			}, array_keys($params)));
		
			$sql = "	DELETE FROM `#__bpmn_execution_variables`
						WHERE `execution_id` = :eid
						AND `name` IN ($placeholders)
			";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue('eid', $execution->getId());
			$stmt->bindAll($params);
			$stmt->execute();
		}
			
		if(!empty($delta[Execution::SYNC_STATE_MODIFIED]))
		{
			$sql = "	INSERT INTO `#__bpmn_execution_variables`
							(`execution_id`, `name`, `value`, `value_blob`)
						VALUES
							(:eid, :name, :value, :blob)
			";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue('eid', $execution->getId());
		
			foreach($delta[Execution::SYNC_STATE_MODIFIED] as $k)
			{
				$value = NULL;
							
				if(is_scalar($syncData['variables'][$k]))
				{
					$val = $syncData['variables'][$k];
		
					if(is_bool($val))
					{
						$val = $val ? '1' : '0';
					}
		
					$value = new UnicodeString(trim($val));
		
					if($value->length() > 250)
					{
						$value = $value->substring(0, 250);
					}
		
					$value = $value->toLowerCase();
				}
								
				$stmt->bindValue('name', $k);
				$stmt->bindValue('value', $value);
				$stmt->bindValue('blob', new BinaryData(serialize($syncData['variables'][$k])));
				$stmt->execute();
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
		
		foreach($vars as $k => $v)
		{
			if(!array_key_exists($k, $syncData))
			{
				$result[0][$k] = true;
				continue;
			}
			
			if($v !== $syncData[$k])
			{
				$result[1][$k] = true;
			}
			
			unset($syncData[$k]);
		}
		
		foreach($syncData as $k => $v)
		{
			$result[1][$k] = true;
		}
		
		return [
			Execution::SYNC_STATE_REMOVED => array_unique(array_keys(array_merge($result[0], $result[1]))),
			Execution::SYNC_STATE_MODIFIED => array_keys($result[1])
		];
		
		return array_map('array_keys', $result);
	}
}
