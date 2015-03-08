<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Job;

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class JobQuery extends AbstractQuery
{
	protected $executionId;
	protected $externalId;
	protected $processInstanceId;
	protected $processDefinitionKey;
	protected $processBusinessKey;
	
	protected $jobId;
	protected $jobRetries;
	protected $jobLockOwner;
	protected $jobHandlerType;
	
	protected $isScheduled;
	protected $isTimer;
	
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function executionId($id)
	{
		$this->populateMultiProperty($this->executionId, $id, function($value) {
			return new UUID($value);
		});
	
		return $this;
	}
	
	public function externalId($id)
	{
		$this->populateMultiProperty($this->externalId, $id);
	
		return $this;
	}
	
	public function processInstanceId($id)
	{
		$this->populateMultiProperty($this->processInstanceId, $id, function($value) {
			return new UUID($value);
		});
	
		return $this;
	}
	
	public function processDefinitionKey($key)
	{
		$this->populateMultiProperty($this->processDefinitionKey, $key);
	
		return $this;
	}
	
	public function processBusinessKey($key)
	{
		$this->populateMultiProperty($this->processBusinessKey, $key, function($value) {
			return new UUID($value);
		});
	
		return $this;
	}
	
	public function jobId($id)
	{
		$this->populateMultiProperty($this->jobId, $id, function($value) {
			return new UUID($value);
		});
	
		return $this;
	}
	
	public function jobRetries($retries)
	{
		$this->populateMultiProperty($this->jobRetries, $retries);
	
		return $this;
	}
	
	public function jobLockOwner($owner)
	{
		$this->populateMultiProperty($this->jobLockOwner, $owner);
	
		return $this;
	}
	
	public function jobHandlerType($handlerType)
	{
		$this->populateMultiProperty($this->jobHandlerType, $handlerType);
	
		return $this;
	}
	
	public function scheduled($scheduled = true)
	{
		$this->isScheduled = $scheduled ? true : false;
		
		return $this;
	}
	
	public function timer($timer = true)
	{
		$this->isTimer = $timer ? true : false;
		
		return $this;
	}
	
	public function orderByProcessInstanceId($ascending = true)
	{
		$this->orderings[] = ['e.`process_id`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByProcessBusinessKey($ascending = true)
	{
		$this->orderings[] = ['e.`business_key`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByProcessDefinitionId($ascending = true)
	{
		$this->orderings[] = ['d.`id`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByProcessDefinitionKey($ascending = true)
	{
		$this->orderings[] = ['d.`process_key`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByExternalId($ascending = true)
	{
		$this->orderings[] = ['j.`external_id`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByCreated($ascending = true)
	{
		$this->orderings[] = ['j.`created_at`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByScheduled($ascending = true)
	{
		$this->orderings[] = ['j.`scheduled_at`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByRun($ascending = true)
	{
		$this->orderings[] = ['j.`run_at`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByHandlerType($ascending = true)
	{
		$this->orderings[] = ['j.`handler_type`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByJobId($ascending = true)
	{
		$this->orderings[] = ['j.`id`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByRetries($ascending = true)
	{
		$this->orderings[] = ['j.`retries`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByLockOwner($ascending = true)
	{
		$this->orderings[] = ['j.`lock_owner`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function count()
	{
		$stmt = $this->executeSql(true);
		
		return (int)$stmt->fetchNextColumn(0);
	}
	
	public function findOne()
	{
		$stmt = $this->executeSql(false, 1);
		$row = $stmt->fetchNextRow();
		
		if($row === false)
		{
			throw new \OutOfBoundsException(sprintf('No matching job found'));
		}
		
		return $this->unserializeJob($row);
	}
	
	public function findAll()
	{
		$stmt = $this->executeSql(false, $this->limit, $this->offset);
		$result = [];
		
		while($row = $stmt->fetchNextRow())
		{
			$result[] = $this->unserializeJob($row);
		}
		
		return $result;
	}
	
	protected function unserializeJob(array $row)
	{
		$job = new Job(
			$row['id'],
			$row['execution_id'],
			$row['handler_type'],
			unserialize(BinaryData::decode($row['handler_data'])),
			new \DateTimeImmutable('@' . $row['created_at']),
			$row['retries'],
			$row['lock_owner']
		);
		
		$job->setExternalId($row['external_id']);
		
		if($row['scheduled_at'] !== NULL)
		{
			$job->setScheduledAt(new \DateTimeImmutable('@' . $row['scheduled_at'], new \DateTimeZone('UTC')));
		}
		
		if($row['run_at'] !== NULL)
		{
			$job->setRunAt(new \DateTimeImmutable('@' . $row['run_at'], new \DateTimeZone('UTC')));
		}
		
		return $job;
	}
	
	protected function getDefaultOrderBy()
	{
		return ['j.`id`', 'ASC'];
	}
	
	protected function executeSql($count = false, $limit = 0, $offset = 0)
	{
		$fields = [];
		
		if($count)
		{
			$fields[] = 'COUNT(*) AS num';
		}
		else
		{
			$fields[] = 'j.*';
		}
		
		$sql = 'SELECT ' . implode(', ', $fields) . ' FROM `#__bpmn_job` AS j';
		$sql .= ' LEFT JOIN `#__bpmn_execution` AS e ON (e.`id` = j.`execution_id`)';
		$sql .= ' LEFT JOIN `#__bpmn_process_definition` AS d ON (d.`id` = e.`definition_id`)';
		
		$where = [];
		$params = [];
		
		$this->buildPredicate("e.`id`", $this->executionId, $where, $params);
		$this->buildPredicate("e.`external_id`", $this->externalId, $where, $params);
		$this->buildPredicate("e.`process_id`", $this->processInstanceId, $where, $params);
		$this->buildPredicate("e.`business_key`", $this->processBusinessKey, $where, $params);
		$this->buildPredicate("d.`process_key`", $this->processDefinitionKey, $where, $params);
		
		$this->buildPredicate("j.`id`", $this->jobId, $where, $params);
		$this->buildPredicate("j.`retries`", $this->jobRetries, $where, $params);
		$this->buildPredicate("j.`lock_owner`", $this->jobLockOwner, $where, $params);
		$this->buildPredicate("j.`handler_type`", $this->jobHandlerType, $where, $params);
		
		if($this->isScheduled === true)
		{
			$where[] = 'j.`scheduled_at` IS NOT NULL';
		}
		elseif($this->isScheduled === false)
		{
			$where[] = 'j.`scheduled_at` IS NULL';
		}
		
		if($this->isTimer === true)
		{
			$where[] = 'j.`run_at` IS NOT NULL';
		}
		elseif($this->isTimer === false)
		{
			$where[] = 'j.`run_at` IS NULL';
		}
		
		if(!empty($where))
		{
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		
		$sql .= $this->buildOrderings();
		
		$stmt = $this->engine->prepareQuery($sql);
		$stmt->bindAll($params);
		$stmt->setLimit($limit);
		$stmt->setOffset($offset);
		$stmt->transform('id', new UUIDTransformer());
		$stmt->transform('execution_id', new UUIDTransformer());
		$stmt->execute();
		
		return $stmt;
	}
}
