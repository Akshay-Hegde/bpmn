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

use KoolKode\BPMN\Engine\AbstractActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\CreateMessageSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Subscribes to a message event and waits for message arrival.
 * 
 * @author Martin Schröder
 */
class IntermediateMessageCatchBehavior extends AbstractActivity implements IntermediateCatchEventInterface
{
	protected $message;
	
	public function __construct($message)
	{
		$this->message = (string)$message;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function enter(VirtualExecution $execution)
	{
		$execution->waitForSignal();
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function processSignal(VirtualExecution $execution, $signal = NULL, array $variables = [])
	{
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
		
		$this->leave($execution);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function createEventSubscriptions(VirtualExecution $execution, $activityId, Node $node = NULL)
	{
		$execution->getEngine()->executeCommand(new CreateMessageSubscriptionCommand(
			$this->message,
			$execution,
			$activityId,
			($node === NULL) ? $execution->getNode() : $node
		));
	}
}
