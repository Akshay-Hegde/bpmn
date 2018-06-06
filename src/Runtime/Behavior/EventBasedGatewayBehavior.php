<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Engine\AbstractActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\History\Event\ActivityCompletedEvent;
use KoolKode\BPMN\History\Event\ActivityStartedEvent;

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
    public function enter(VirtualExecution $execution): void
    {
        $model = $execution->getProcessModel();
        $gateway = $execution->getNode();
        $transitions = $model->findOutgoingTransitions($gateway->getId());
        
        if (\count($transitions) < 2) {
            throw new \RuntimeException(\sprintf('Event based gateway %s must be connected to at least 2 intermediate catch events', $gateway->getId()));
        }
        
        foreach ($transitions as $trans) {
            $eventNode = $model->findNode($trans->getTo());
            $behavior = $eventNode->getBehavior();
            
            if (!$behavior instanceof IntermediateCatchEventInterface) {
                throw new \RuntimeException(\sprintf('Unsupported node behavior found after event based gatetway %s: %s', $execution->getNode()->getId(), \get_class($behavior)));
            }
            
            $behavior->createEventSubscriptions($execution, $execution->getNode()->getId(), $eventNode);
        }
        
        $execution->waitForSignal();
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        $engine = $execution->getEngine();
        $engine->notify(new ActivityCompletedEvent($execution->getNode()->getId(), $execution, $engine));
        
        $node = $execution->getProcessModel()->findNode($delegation['nodeId']);
        $name = $this->getName($execution->getExpressionContext()) ?? '';
        
        $engine->notify(new ActivityStartedEvent($node->getId(), $name, $execution, $engine));
        
        $this->delegateSignal($execution, $signal, $variables, $delegation);
    }
}
