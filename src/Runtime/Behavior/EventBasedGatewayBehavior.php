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

/**
 * Exclusive gateway based on intermediate catch events connected to it.
 * 
 * @author Martin Schröder
 */
class EventBasedGatewayBehavior extends AbstractActivity
{
	/**
	 * {@inheritdoc}
	 */
	public function enter(VirtualExecution $execution)
	{
		$model = $execution->getProcessModel();
		$gateway = $execution->getNode();
		$transitions = $model->findOutgoingTransitions($gateway->getId());
		
		if(count($transitions) < 2)
		{
			throw new \RuntimeException(sprintf('Event based gateway must be connected to at least 2 intermediate catch events'));
		}
		
		foreach($transitions as $trans)
		{
			$eventNode = $model->findNode($trans->getTo());
			$behavior = $eventNode->getBehavior();
			
			if(!$behavior instanceof IntermediateCatchEventInterface)
			{
				throw new \RuntimeException(sprintf(
					'Unsupported node behavior found after event based gatetway %s: %s',
					$execution->getNode()->getId(),
					get_class($behavior)
				));
			}
			
			$behavior->createEventSubscriptions($execution, $execution->getNode()->getId(), $eventNode);
		}
		
		$execution->waitForSignal();
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
	{
		$this->delegateSignal($execution, $signal, $variables, $delegation);
	}
}
