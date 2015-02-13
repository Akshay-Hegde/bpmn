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
	protected $processInstanceId;
	protected $processDefinitionKey;
	protected $processBusinessKey;
	
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
		$stmt = $this->executeSql();
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
			$row['retries'],
			$row['lock_owner']
		);
		
		return $job;
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
		$this->buildPredicate("e.`process_id`", $this->processInstanceId, $where, $params);
		$this->buildPredicate("e.`business_key`", $this->processBusinessKey, $where, $params);
		$this->buildPredicate("d.`process_key`", $this->processDefinitionKey, $where, $params);
		
		if(!empty($where))
		{
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		
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
