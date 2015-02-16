<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Engine\AbstractSignalableBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\CreateTimerSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * 
 * 
 * @author Martin Schröder
 */
class IntermediateTimerDurationBehavior extends AbstractSignalableBehavior implements IntermediateCatchEventInterface
{
	protected $duration;
	
	public function __construct($duration)
	{
		$this->duration = (string)$duration;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$this->createEventSubscription($execution, $execution->getNode()->getId());
		
		$execution->waitForSignal();
	}
	
	public function createEventSubscription(VirtualExecution $execution, $activityId, Node $node = NULL)
	{
		$interval = new \DateInterval($this->duration);
		$now = new \DateTimeImmutable();
		$time = $now->add($interval);
		
		$execution->getEngine()->executeCommand(new CreateTimerSubscriptionCommand(
			$execution,
			$time,
			$activityId,
			($node === NULL) ? $execution->getNode() : $node
		));
	}
}
