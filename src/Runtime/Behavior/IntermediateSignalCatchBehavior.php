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
use KoolKode\BPMN\Runtime\Command\CreateSignalSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Subscribes to a signal event and waits for the signals arrival.
 * 
 * @author Martin Schröder
 */
class IntermediateSignalCatchBehavior extends AbstractActivity implements IntermediateCatchEventInterface
{
	protected $signal;
	
	public function __construct($signal)
	{
		$this->signal = (string)$signal;
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function enter(VirtualExecution $execution)
	{
		$execution->waitForSignal();
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function processSignal(VirtualExecution $execution, $signal = NULL, array $variables = [])
	{
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
		
		$execution->takeAll();
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function createEventSubscriptions(VirtualExecution $execution, $activityId, Node $node = NULL)
	{
		$execution->getEngine()->pushCommand(new CreateSignalSubscriptionCommand(
			$this->signal,
			$execution,
			$activityId,
			($node === NULL) ? $execution->getNode() : $node
		));
		
		parent::createEventSubscriptions($execution, $activityId, $node);
	}
}
