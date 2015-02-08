<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Repository\ProcessDefinition;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Process\Command\SignalExecutionCommand;
use KoolKode\Util\UUID;

/**
 * Notifies all executions that habe subscribed to the received signal.
 * 
 * @author Martin Schröder
 */
class SignalEventReceivedCommand extends AbstractBusinessCommand
{
	protected $signal;
	
	protected $variables;
	
	protected $executionId;
	
	protected $sourceExecutionId;
	
	public function __construct($signal, UUID $executionId = NULL, array $variables = [], VirtualExecution $sourceExecution = NULL)
	{
		$this->signal = (string)$signal;
		$this->variables = $variables;
		$this->executionId = $executionId;
		$this->sourceExecutionId = ($sourceExecution === NULL) ? NULL : $sourceExecution->getId();
	}
	
	public function isSerializable()
	{
		return true;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT - 100;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$sql = "	SELECT s.`id`, s.`execution_id`, s.`activity_id`, s.`node`
					FROM `#__bpmn_event_subscription` AS s
					INNER JOIN `#__bpmn_execution` AS e ON (e.`id` = s.`execution_id`)
					WHERE s.`name` = :signal
					AND s.`flags` = :flags
					ORDER BY e.`depth` DESC
		";
			
		if($this->executionId !== NULL)
		{
			$sql .= ' AND s.`execution_id` = :eid';
		}
			
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('signal', $this->signal);
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_SIGNAL);
			
		if($this->executionId !== NULL)
		{
			$stmt->bindValue('eid', $this->executionId);
		}
		
		$stmt->transform('execution_id', new UUIDTransformer());
		$stmt->execute();
		
		$ids = [];
		$executions = [];
		
		foreach($stmt->fetchRows() as $row)
		{
			$execution = $executions[] = $engine->findExecution($row['execution_id']);
			$ids[(string)$execution->getId()] = [$execution->getId(), $row['activity_id']];
			
			if($row['node'] !== NULL)
			{
				$execution->setNode($execution->getProcessModel()->findNode($row['node']));
				$execution->setTransition(NULL);
			}
		}
		
		if(!empty($ids))
		{
			$sql = "	DELETE FROM `#__bpmn_event_subscription`
						WHERE `execution_id` = :eid
						AND `activity_id` = :aid
			";
			$stmt = $engine->prepareQuery($sql);
			
			foreach($ids as $tmp)
			{
				$stmt->bindValue('eid', $tmp[0]);
				$stmt->bindValue('aid', $tmp[1]);
				$stmt->execute();
			}
		}
		
		$uuids = [];
		
		foreach($executions as $execution)
		{
			$uuids[] = $execution->getId();
			
			$engine->pushCommand(new SignalExecutionCommand($execution, $this->signal, $this->variables));
		}
		
		// Include signal start events subscriptions.
		$sql = "	SELECT s.`name` AS signal_name, d.* 
					FROM `#__bpmn_process_subscription` AS s
					INNER JOIN `#__bpmn_process_definition` AS d ON (d.`id` = s.`definition_id`)
					WHERE s.`flags` = :flags
					AND s.`name` = :name
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_SIGNAL);
		$stmt->bindValue('name', $this->signal);
		$stmt->transform('id', new UUIDTransformer());
		$stmt->transform('deployment_id', new UUIDTransformer());
		$stmt->execute();
		
		$source = ($this->sourceExecutionId === NULL) ? NULL : $engine->findExecution($this->sourceExecutionId);
		
		while($row = $stmt->fetchNextRow())
		{
			$definition = new ProcessDefinition(
				$row['id'],
				$row['process_key'],
				$row['revision'],
				unserialize(BinaryData::decode($row['definition'])),
				$row['name'],
				new \DateTimeImmutable('@' . $row['deployed_at']),
				$row['deployment_id']
			);
			
			$uuids[] = $engine->executeCommand(new StartProcessInstanceCommand(
				$definition,
				$definition->findSignalStartEvent($row['signal_name']),
				($source === NULL) ? NULL : $source->getBusinessKey(),
				$this->variables
			));
		}
				
		if($source !== NULL)
		{
			$engine->pushCommand(new SignalExecutionCommand($source));
		}
		
		return $uuids;
	}
}
