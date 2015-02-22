<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\History;

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class HistoricActivityInstanceQuery extends AbstractQuery
{
	protected $engine;
	
	protected $activityId;
	
	protected $executionId;
	
	protected $processInstanceId;
	
	protected $activityDefinitionKey;
	
	protected $completed;
	
	protected $canceled;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function activityId($id)
	{
		$this->populateMultiProperty($this->activityId, $id, function($value) {
			return new UUID($value);
		});
	
		return $this;
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
	
	public function activityDefinitionKey($definitionKey)
	{
		$this->populateMultiProperty($this->activityDefinitionKey, $definitionKey, function($value) {
			return (string)$value;
		});
	
		return $this;
	}
	
	public function completed($completed)
	{
		$this->completed = $completed ? true : false;
		
		return $this;
	}
	
	public function canceled($canceled)
	{
		$this->canceled = $canceled ? true : false;
	
		return $this;
	}
	
	public function orderByStartedAt($ascending = true)
	{
		$this->orderings[] = ['a.`started_at`', $ascending ? 'ASC' : 'DESC'];
		
		return $this;
	}
	
	public function orderByEndedAt($ascending = true)
	{
		$this->orderings[] = ['a.`ended_at`', $ascending ? 'ASC' : 'DESC'];
	
		return $this;
	}
	
	public function orderByDuration($ascending = true)
	{
		$this->orderings[] = ['a.`duration`', $ascending ? 'ASC' : 'DESC'];
	
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
			throw new \OutOfBoundsException(sprintf('No matching historic activity instance found'));
		}
	
		return $this->unserializeActivity($row);
	}
	
	public function findAll()
	{
		$stmt = $this->executeSql();
		$result = [];
	
		while($row = $stmt->fetchNextRow())
		{
			$result[] = $this->unserializeActivity($row);
		}
	
		return $result;
	}
	
	protected function unserializeActivity(array $row)
	{
		$activity = new HistoricActivityInstance(
			$row['id'],
			$row['execution_id'],
			$row['activity'],
			$row['started_at']
		);
		
		$activity->setEndedAt($row['ended_at']);
		
		if($row['duration'] !== NULL)
		{
			$activity->setDuration((float)$row['duration'] / 1000 + .001);
		}
		
		$activity->setCompleted($row['completed']);
	
		return $activity;
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
			$fields[] = 'a.*';
		}
	
		$sql = 'SELECT ' . implode(', ', $fields) . ' FROM `#__bpmn_history_activity` AS a';
		$sql .= ' INNER JOIN `#__bpmn_history_execution` AS e ON (e.`id` = a.`execution_id`)';
	
		$where = [];
		$params = [];
	
		$this->buildPredicate("a.`id`", $this->activityId, $where, $params);
		$this->buildPredicate("e.`id`", $this->executionId, $where, $params);
		$this->buildPredicate("e.`process_id`", $this->processInstanceId, $where, $params);
		$this->buildPredicate("a.`activity`", $this->activityDefinitionKey, $where, $params);
		
		if($this->completed === true)
		{
			$where[] = 'a.`completed` = 1';
		}
		elseif($this->completed === false)
		{
			$where[] = 'a.`completed` = 0';
		}
		
		if($this->canceled === true)
		{
			$where[] = 'a.`duration` IS NOT NULL AND a.`completed` = 0';
		}
		elseif($this->canceled === false)
		{
			$where[] = 'a.`duration` IS NULL OR a.`completed` = 1';
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
		$stmt->transform('task_id', new UUIDTransformer());
		$stmt->transform('started_at', new DateTimeMillisTransformer());
		$stmt->transform('ended_at', new DateTimeMillisTransformer());
		$stmt->execute();
	
		return $stmt;
	}
}
