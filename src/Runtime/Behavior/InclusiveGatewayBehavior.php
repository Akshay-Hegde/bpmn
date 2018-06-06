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

use KoolKode\BPMN\Engine\BasicAttributesTrait;
use KoolKode\BPMN\History\Event\ActivityCompletedEvent;
use KoolKode\BPMN\History\Event\ActivityStartedEvent;
use KoolKode\Process\Execution;
use KoolKode\Process\Behavior\InclusiveChoiceBehavior;

/**
 * Chooses any number of outgoing sequence flows that have conditions evaluating to true.
 * 
 * @author Martin Schröder
 */
class InclusiveGatewayBehavior extends InclusiveChoiceBehavior
{
    use BasicAttributesTrait;

    public function setDefaultFlow(?string $flow)
    {
        $this->defaultTransition = $flow;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Execution $execution): void
    {
        $engine = $execution->getEngine();
        $id = $execution->getNode()->getId();
        
        $name = $this->getStringValue($this->name, $execution->getExpressionContext()) ?? '';
        
        $engine->notify(new ActivityStartedEvent($id, $name, $execution, $engine));
        
        parent::execute($execution);
        
        $engine->notify(new ActivityCompletedEvent($id, $execution, $engine));
    }
}
