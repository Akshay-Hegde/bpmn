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

    public function __construct(string $signal)
    {
        $this->signal = $signal;
    }

    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $execution->waitForSignal();
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        if ($signal !== $this->signal) {
            throw new \RuntimeException(\sprintf('Catch event awaits signal "%s", unable to process signal "%s"', $this->signal, $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        $this->leave($execution);
    }

    /**
     * {@inheritdoc}
     */
    public function createEventSubscriptions(VirtualExecution $execution, string $activityId, ?Node $node = null): void
    {
        $execution->getEngine()->pushCommand(new CreateSignalSubscriptionCommand($this->signal, $execution, $activityId, ($node === null) ? $execution->getNode() : $node));
        
        parent::createEventSubscriptions($execution, $activityId, $node);
    }
}
