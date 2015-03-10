<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Delegate\Behavior;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Behavior\IntermediateCatchEventInterface;
use KoolKode\BPMN\Runtime\Command\CreateMessageSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Receive task that waits for arrival of a message.
 * 
 * @author Martin Schröder
 */
class ReceiveMessageTaskBehavior extends AbstractScopeActivity implements IntermediateCatchEventInterface
{
	protected $message;
	
	public function __construct($activityId, $message)
	{
		parent::__construct($activityId);
		
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
	public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
	{
		if($signal !== $this->message)
		{
			throw new \RuntimeException(sprintf('Receive task awaits message "%s", unable to process signal "%s"', $this->message, $signal));
		}
		
		$this->passVariablesToExecution($execution, $variables);
		
		$engine = $execution->getEngine();
		$name = $this->getStringValue($this->name, $execution->getExpressionContext());
		
		$engine->debug('Receive task "{task}" triggered by message <{message}>', [
			'task' => $name,
			'message' => $signal
		]);
		
		$engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
		
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
