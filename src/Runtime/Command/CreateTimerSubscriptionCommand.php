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
use KoolKode\BPMN\Job\Handler\AsyncCommandHandler;
use KoolKode\Process\Command\SignalExecutionCommand;
use KoolKode\Process\Node;
use KoolKode\Util\UUID;

/**
 * Creates a timer event subscription.
 * 
 * @author Martin Schröder
 */
class CreateTimerSubscriptionCommand extends AbstractBusinessCommand
{
	protected $executionId;
	
	protected $time;
	
	protected $activityId;
	
	protected $nodeId;
	
	public function __construct(VirtualExecution $execution, \DateTimeInterface $time, $activityId, Node $node = NULL)
	{
		$this->executionId = $execution->getId();
		$this->time = new \DateTimeImmutable('@' . $time->getTimestamp(), new \DateTimeZone('UTC'));
		$this->activityId = (string)$activityId;
		$this->nodeId = ($node === NULL) ? NULL : (string)$node->getId();
	}
	
	public function isSerializable()
	{
		return true;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$id = UUID::createRandom();
		$execution = $engine->findExecution($this->executionId);
		$nodeId = ($this->nodeId === NULL) ? NULL : $execution->getProcessModel()->findNode($this->nodeId)->getId();
		
		$job = $engine->scheduleJob($execution, AsyncCommandHandler::HANDLER_TYPE, [
			AsyncCommandHandler::PARAM_COMMAND => new SignalExecutionCommand($execution),
			AsyncCommandHandler::PARAM_NODE_ID => $nodeId
		], $this->time);
		
		$sql = "
			INSERT INTO `#__bpmn_event_subscription`
				(`id`, `execution_id`, `activity_id`, `node`, `process_instance_id`, `flags`, `name`, `created_at`, `job_id`)
			VALUES
				(:id, :eid, :aid, :node, :pid, :flags, :signal, :created, :job)
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('id', $id);
		$stmt->bindValue('eid', $execution->getId());
		$stmt->bindValue('aid', $this->activityId);
		$stmt->bindValue('node', $nodeId);
		$stmt->bindValue('pid', $execution->getRootExecution()->getId());
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_TIMER);
		$stmt->bindValue('signal', 'timer');
		$stmt->bindValue('created', time());
		$stmt->bindValue('job', $job->getId());
		$stmt->execute();
		
		$engine->debug('{execution} subscribed to timer job <{job}>', [
			'execution' => (string)$execution,
			'job' => (string)$job->getId()
		]);
		
		return $id;
	}
}
