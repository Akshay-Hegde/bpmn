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
		$this->createEventSubscriptions($execution, $execution->getNode()->getId());
		
		$this->enter($this->processExecution($execution));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function signal(Execution $execution, $signal, array $variables = [])
	{
		$this->processSignal($execution, $signal, $variables);
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
	 * @param VirtualExecution $execution
	 * @param string $signal
	 * @param array $variables
	 */
	public function processSignal(VirtualExecution $execution, $signal, array $variables = [])
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
		$this->clearEventSubscriptions($execution, $execution->getNode()->getId());
		
		$execution->takeAll($transitions);
	}
	
	protected function processExecution(VirtualExecution $execution)
	{
		return $execution;
	}
}
