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

use KoolKode\BPMN\History\Event\ActivityCompletedEvent;
use KoolKode\BPMN\History\Event\ActivityStartedEvent;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Process\Execution;
use KoolKode\Process\Node;

/**
 * Base for activity implementations.
 * 
 * @author Martin Schröder
 */
abstract class AbstractActivity implements ActivityInterface
{
	use BasicAttributesTrait;
	
	/**
	 * {@inheritdoc}
	 */
	public function execute(Execution $execution)
	{
		$execution->getEngine()->notify(new ActivityStartedEvent($execution->getNode()->getId(), $execution, $execution->getEngine()));
		
		$this->createEventSubscriptions($execution, $execution->getNode()->getId());
		
		$this->enter($execution);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function signal(Execution $execution, $signal, array $variables = [], array $delegation = [])
	{
		$this->processSignal($execution, $signal, $variables, $delegation);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function clearEventSubscriptions(VirtualExecution $execution, $activityId)
	{
		$engine = $execution->getEngine();
		
		// Delete timer jobs:
		$stmt = $engine->prepareQuery("
			SELECT `job_id`
			FROM `#__bpmn_event_subscription`
			WHERE `execution_id` = :eid
			AND `activity_id` = :aid
			AND `flags` = :flags
			AND `job_id` IS NOT NULL
		");
		$stmt->bindValue('eid', $execution->getId());
		$stmt->bindValue('aid', $activityId);
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_TIMER);
		$stmt->transform('job_id', new UUIDTransformer());
		$stmt->execute();
		
		$management = $engine->getManagementService();
		
		foreach($stmt->fetchColumns('job_id') as $jobId)
		{
			$management->removeJob($jobId);
		}
		
		$sql = "
			DELETE FROM `#__bpmn_event_subscription`
			WHERE `execution_id` = :eid
			AND `activity_id` = :aid
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('eid', $execution->getId());
		$stmt->bindValue('aid', $activityId);
		$count = $stmt->execute();
		
		if($count > 0)
		{
			$message = sprintf('Cleared {count} event subscription%s related to activity <{activity}> within {execution}', ($count == 1) ? '' : 's');
				
			$engine->debug($message, [
				'count' => $count,
				'activity' => $activityId,
				'execution' => (string)$execution
			]);
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function createEventSubscriptions(VirtualExecution $execution, $activityId, Node $node = NULL) { }
	
	/**
	 * Process the given signal, roughly equivalent to the signal() method of a SignalableBehavior.
	 * 
	 * @param VirtualExecution $execution target execution.
	 * @param string $signal Name of the signal.
	 * @param array<string, mixed> $variables Signal data.
	 * @param array<string, mixed> $delegation Signal delegation data.
	 */
	public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
	{
		throw new \RuntimeException(sprintf('Signal <%s> is not supported by activity %s', ($signal === NULL) ? 'NULL' : $signal, get_class($this)));
	}
	
	/**
	 * Enter the activity, this is roughly equal to calling execute() on standard a behavior.
	 * 
	 * @param VirtualExecution $execution
	 */
	public function enter(VirtualExecution $execution)
	{
		$this->leave($execution);
	}
	
	/**
	 * Have the given execution leave the activity.
	 * 
	 * @param VirtualExecution $execution
	 * @param array $transitions
	 */
	public function leave(VirtualExecution $execution, array $transitions = NULL)
	{
		$execution->getEngine()->notify(new ActivityCompletedEvent($execution->getNode()->getId(), $execution, $execution->getEngine()));
		
		$this->clearEventSubscriptions($execution, $execution->getNode()->getId());
		
		$execution->takeAll($transitions);
	}
	
	/**
	 * Pass all variables given to the execution setting them in the executions's scope.
	 * 
	 * @param VirtualExecution $execution
	 * @param array $variables
	 */
	protected function passVariablesToExecution(VirtualExecution $execution, array $variables)
	{
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
	}
	
	/**
	 * Delegate signal to a target node using the same execution.
	 * 
	 * @param VirtualExecution $execution
	 * @param string $signal
	 * @param array $variables
	 * @param array $delegation
	 * @return boolean Returns true if the signal could be delegated.
	 */
	protected function delegateSignal(VirtualExecution $execution, $signal, array $variables, array $delegation)
	{
		if(empty($delegation['nodeId']))
		{
			return false;
		}
		
		$node = $execution->getProcessModel()->findNode($delegation['nodeId']);
		
		$execution->getEngine()->debug('Delegating signal <{signal}> to {node}', [
			'signal' => ($signal === NULL) ? 'NULL' : $signal,
			'node' => (string)$node
		]);
		
		$execution->setNode($node);
		$execution->waitForSignal();
		
		$execution->signal($signal, $variables);
		
		return true;
	}
}
