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

use KoolKode\BPMN\Engine\AbstractBoundaryActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\CreateMessageSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Message catch event bound to an event scope.
 * 
 * @author Martin Schröder
 */
class MessageBoundaryEventBehavior extends AbstractBoundaryActivity
{
	protected $message;
	
	public function __construct($activityId, $attachedTo, $message)
	{
		parent::__construct($activityId, $attachedTo);
		
		$this->message = (string)$message;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
	{
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
		
		parent::processSignal($execution, $signal, $variables);
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
			$node,
			true
		));
	}
}
