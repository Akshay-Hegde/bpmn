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

class HistoricProcessInstanceQuery extends AbstractQuery
{
	protected $engine;
	
	protected $processInstanceId;
	
	protected $processDefinitionId;
	
	protected $processDefinitionKey;
	
	protected $processBusinessKey;
	
	protected $startActivityId;
	
	protected $endActivityId;
	
	protected $finished;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function processInstanceId($id)
	{
		$this->populateMultiProperty($this->processInstanceId, $id, function($value) {
			return new UUID($value);
		});
	
		return $this;
	}
	
	public function processDefinitionId($id)
	{
		$this->populateMultiProperty($this->processDefinitionId, $id, function($value) {
			return new UUID($value);
		});
	
		return $this;
	}
	
	public function processDefinitionKey($key)
	{
		$this->populateMultiProperty($this->processDefinitionKey, $key, function($value) {
			return (string)$value;
		});
	
		return $this;
	}
	
	public function processBusinessKey($key)
	{
		$this->populateMultiProperty($this->processBusinessKey, $key);
	
		return $this;
	}
	
	public function startActivityId($id)
	{
		$this->populateMultiProperty($this->startActivityId, $id);
	
		return $this;
	}
	
	public function endActivityId($id)
	{
		$this->populateMultiProperty($this->endActivityId, $id);
	
		return $this;
	}
	
	public function finished($finished)
	{
		$this->finished = $finished ? true : false;
		
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
			throw new \OutOfBoundsException(sprintf('No matching historic process instance found'));
		}
	
		return $this->unserializeProcess($row);
	}
	
	public function findAll()
	{
		$stmt = $this->executeSql();
		$result = [];
	
		while($row = $stmt->fetchNextRow())
		{
			$result[] = $this->unserializeProcess($row);
		}
	
		return $result;
	}
	
	protected function unserializeProcess(array $row)
	{
		$process = new HistoricProcessInstance(
			$row['id'],
			$row['definition_id'],
			$row['process_key'],
			$row['start_activity'],
			$row['started_at']
		);
		
		$process->setBusinessKey($row['business_key']);
		$process->setEndActivityId($row['end_activity']);
		$process->setEndedAt($row['ended_at']);
		
		if($row['duration'] !== NULL)
		{
			$process->setDuration((float)$row['duration'] / 1000 + .001);
		}
	
		return $process;
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
			$fields[] = 'p.*';
			$fields[] = 'd.`process_key`';
		}
	
		$sql = 'SELECT ' . implode(', ', $fields) . ' FROM `#__bpmn_history_process` AS p';
		$sql .= ' INNER JOIN `#__bpmn_process_definition` AS d ON (p.`definition_id` = d.`id`)';
	
		$where = [];
		$params = [];
	
		$this->buildPredicate("p.`id`", $this->processInstanceId, $where, $params);
		$this->buildPredicate('p.`business_key`', $this->processBusinessKey, $where, $params);
		$this->buildPredicate("p.`start_activity`", $this->startActivityId, $where, $params);
		$this->buildPredicate("p.`end_activity`", $this->endActivityId, $where, $params);
		$this->buildPredicate('d.`id`', $this->processDefinitionId, $where, $params);
		$this->buildPredicate('d.`process_key`', $this->processDefinitionKey, $where, $params);
		
		if($this->finished === true)
		{
			$where[] = 'p.`ended_at` IS NOT NULL';
		}
		elseif($this->finished === false)
		{
			$where[] = 'p.`ended_at` IS NULL';
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
		$stmt->transform('definition_id', new UUIDTransformer());
		$stmt->transform('started_at', new DateTimeMillisTransformer());
		$stmt->transform('ended_at', new DateTimeMillisTransformer());
		$stmt->execute();
	
		return $stmt;
	}
}
