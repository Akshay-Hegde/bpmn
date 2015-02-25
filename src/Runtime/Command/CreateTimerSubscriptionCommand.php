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
class CreateTimerSubscriptionCommand extends AbstractCreateSubscriptionCommand
{
	/**
	 * UNIX timestamp of the scheduled job execution date.
	 * 
	 * @var integer
	 */
	protected $time;
	
	/**
	 * Created a timer event subscription backed by a scheduled job.
	 * 
	 * @param \DateTimeInterface $time Schedule date.
	 * @param VirtualExecution $execution Target execution.
	 * @param string $activityId ID of the activity that created the event subscription.
	 * @param Node $node Target node to receive the delegated signal or NULL in order to use the activity node.
	 * @param boolean $boundaryEvent Is this a subscription for a boundary event?
	 */
	public function __construct(\DateTimeInterface $time, VirtualExecution $execution, $activityId, Node $node = NULL, $boundaryEvent = false)
	{
		parent::__construct('timer', $execution, $activityId, $node, $boundaryEvent);
		
		$this->time = $time->getTimestamp();
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function executeCommand(ProcessEngine $engine)
	{
		$id = UUID::createRandom();
		$execution = $engine->findExecution($this->executionId);
		$nodeId = ($this->nodeId === NULL) ? NULL : $execution->getProcessModel()->findNode($this->nodeId)->getId();
		
		$job = $engine->scheduleJob($execution, AsyncCommandHandler::HANDLER_TYPE, [
			AsyncCommandHandler::PARAM_COMMAND => new SignalExecutionCommand($execution),
			AsyncCommandHandler::PARAM_NODE_ID => $nodeId
		], new \DateTimeImmutable('@' . $this->time, new \DateTimeZone('UTC')));
		
		$this->createSubscription($engine, $job);
		
		$engine->debug('{execution} subscribed to timer job <{job}>', [
			'execution' => (string)$execution,
			'job' => ($job === NULL) ? 'NULL' : (string)$job->getId()
		]);
		
		return $id;
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function getSubscriptionFlag()
	{
		return ProcessEngine::SUB_FLAG_TIMER;
	}
}
