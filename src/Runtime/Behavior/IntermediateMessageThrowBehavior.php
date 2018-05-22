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
use KoolKode\BPMN\Runtime\Command\ThrowMessageCommand;

/**
 * Triggers a ThrowMessageEvent that must be handled in application code.
 * 
 * @author Martin Schröder
 */
class IntermediateMessageThrowBehavior extends AbstractActivity
{
    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $execution->getEngine()->pushCommand(new ThrowMessageCommand($execution));
        
        $execution->waitForSignal();
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        $this->passVariablesToExecution($execution, $variables);
        
        $this->leave($execution);
    }
}
