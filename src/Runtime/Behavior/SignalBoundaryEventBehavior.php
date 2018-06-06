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
use KoolKode\BPMN\Runtime\Command\CreateSignalSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Signal catch event bound to an event scope.
 * 
 * @author Martin Schröder
 */
class SignalBoundaryEventBehavior extends AbstractBoundaryActivity
{
    protected $signal;

    public function __construct(string $activityId, string $attachedTo, string $signal)
    {
        parent::__construct($activityId, $attachedTo);
        
        $this->signal = $signal;
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        if ($signal !== $this->signal) {
            throw new \RuntimeException(\sprintf('Boundary event awaits signal "%s", unable to process signal "%s"', $this->signal, $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        parent::processSignal($execution, $signal, $variables);
    }

    /**
     * {@inheritdoc}
     */
    public function createEventSubscriptions(VirtualExecution $execution, string $activityId, ?Node $node = null): void
    {
        $execution->getEngine()->executeCommand(new CreateSignalSubscriptionCommand($this->signal, $execution, $activityId, $node, true));
    }
}
