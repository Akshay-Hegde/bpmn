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
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

/**
 * Delivers a message to an execution that has subscribed to the received message.
 * 
 * @author Martin Schröder
 */
class MessageEventReceivedCommand extends AbstractBusinessCommand
{
	protected $messageName;
	protected $executionId;
	protected $variables;
	
	public function __construct($messageName, UUID $executionId, array $variables = [])
	{
		$this->messageName = (string)$messageName;
		$this->executionId = $executionId;
		$this->variables = $variables;
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
					WHERE s.`name` = :message
					AND s.`flags` = :flags
					AND s.`execution_id` = :eid
					ORDER BY e.`depth` DESC, s.`created_at`
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('message', $this->messageName);
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_MESSAGE);
		$stmt->bindValue('eid', $this->executionId);
		$stmt->setLimit(1);
		$stmt->execute();
		
		$row = $stmt->fetchNextRow();
		
		if($row === false)
		{
			throw new \RuntimeException(sprintf('Execution %s has not subscribed to message %s', $this->executionId, $this->messageName));
		}
		
		$execution = $engine->findExecution($this->executionId);
		
		$delegation = [];
		
		if($row['node'] !== NULL)
		{
			$delegation['nodeId'] = $row['node'];
		}
		
		// Delete timer jobs:
		$stmt = $engine->prepareQuery("
			SELECT `job_id`
			FROM `#__bpmn_event_subscription`
			WHERE `execution_id` = :eid
			AND `activity_id` = :aid
			AND `flags` = :flags
		");
		$stmt->bindValue('eid', $execution->getId());
		$stmt->bindValue('aid', $row['activity_id']);
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_TIMER);
		$stmt->transform('job_id', new UUIDTransformer());
		$stmt->execute();
		
		$management = $engine->getManagementService();
		
		foreach($stmt->fetchColumns('job_id') as $jobId)
		{
			$management->removeJob($jobId);
		}
		
		$sql = "	DELETE FROM `#__bpmn_event_subscription`
					WHERE `execution_id` = :eid
					AND `activity_id` = :aid
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('eid', $execution->getId());
		$stmt->bindValue('aid', $row['activity_id']);
		$count = $stmt->execute();
		
		$message = sprintf('Cleared {count} event subscription%s related to activity <{activity}> within {execution}', ($count == 1) ? '' : 's');
			
		$engine->debug($message, [
			'count' => $count,
			'activity' => $row['activity_id'],
			'execution' => (string)$execution
		]);
		
		$execution->signal($this->messageName, $this->variables, $delegation);
	}
}
