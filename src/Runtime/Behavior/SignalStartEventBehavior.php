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
 * Similar to basic start event, signal subscriptions are handled by repository services.
 * 
 * @author Martin Schröder
 */
class SignalStartEventBehavior extends AbstractActivity implements StartEventBehaviorInterface
{
    protected $signal;

    protected $subProcessStart;

    protected $interrupting = true;

    public function __construct(string $signal, bool $subProcessStart = false)
    {
        $this->signal = $signal;
        $this->subProcessStart = $subProcessStart;
    }

    public function getSignalName(): string
    {
        return $this->signal;
    }

    public function isSubProcessStart(): bool
    {
        return $this->subProcessStart;
    }

    public function isInterrupting(): bool
    {
        return $this->interrupting;
    }

    public function setInterrupting(bool $interrupting): void
    {
        $this->interrupting = $interrupting;
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        if ($signal !== $this->signal) {
            throw new \RuntimeException(sprintf('Start event awaits signal "%s", unable to process signal "%s"', $this->signal, $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        $this->leave($execution);
    }

    /**
     * {@inheritdoc}
     */
    public function createEventSubscriptions(VirtualExecution $execution, string $activityId, ?Node $node = null): void
    {
        $execution->getEngine()->executeCommand(new CreateSignalSubscriptionCommand($this->signal, $execution, $activityId, ($node === null) ? $execution->getNode() : $node));
    }
}
