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
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Database\UUIDTransformer;

/**
 * Clears all event subscriptions related to an execution.
 * 
 * @author Martin Schröder
 */
class ClearEventSubscriptionsCommand extends AbstractBusinessCommand
{
	protected $executionId;
	
	protected $activitId;
	
	public function __construct(VirtualExecution $execution, $activityId)
	{
		$this->executionId = $execution->getId();
		$this->activitId = (string)$activityId;
	}
	
	public function isSerializable()
	{
		return true;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT * 10;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$execution = $engine->findExecution($this->executionId);
		
		// Delete timer jobs:
		$stmt = $engine->prepareQuery("
			SELECT `job_id`
			FROM `#__bpmn_event_subscription`
			WHERE `execution_id` = :eid
			AND `activity_id` = :aid
			AND `flags` = :flags
		");
		$stmt->bindValue('eid', $execution->getId());
		$stmt->bindValue('aid', $this->activitId);
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
		$stmt->bindValue('aid', $this->activitId);
		$count = $stmt->execute();
		
		if($count > 0)
		{
			$message = sprintf('Cleared {count} event subscription%s related to activity <{activity}> within {execution}', ($count == 1) ? '' : 's');
			
			$engine->debug($message, [
				'count' => $count,
				'activity' => $this->activitId,
				'execution' => (string)$execution
			]);
		}
	}
}
