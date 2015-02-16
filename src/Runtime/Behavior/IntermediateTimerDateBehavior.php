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
use KoolKode\Expression\ExpressionInterface;
use KoolKode\Process\Node;

/**
 * @author Martin Schröder
 */
class IntermediateTimerDateBehavior extends AbstractSignalableBehavior implements IntermediateCatchEventInterface
{
	protected $date;
	
	public function setDate(ExpressionInterface $date)
	{
		$this->date = $date;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$this->createEventSubscription($execution, $execution->getNode()->getId());
		
		$execution->waitForSignal();
	}
	
	public function createEventSubscription(VirtualExecution $execution, $activityId, Node $node = NULL)
	{
		$date = $this->getDateValue($this->date, $execution->getExpressionContext());
		
		if(!$date instanceof \DateTimeInterface)
		{
			throw new \RuntimeException(sprintf('Expecting DateTimeInterface, given %s', is_object($date) ? get_class($date) : gettype($date)));
		}
		
		$execution->getEngine()->executeCommand(new CreateTimerSubscriptionCommand(
			$execution,
			$date,
			$activityId,
			($node === NULL) ? $execution->getNode() : $node
		));
	}
}
