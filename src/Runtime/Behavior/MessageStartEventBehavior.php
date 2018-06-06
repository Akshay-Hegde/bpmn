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
use KoolKode\BPMN\Runtime\Command\CreateMessageSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Similar to basic start event, message subscriptions are handled by repository services.
 * 
 * @author Martin Schröder
 */
class MessageStartEventBehavior extends AbstractActivity implements StartEventBehaviorInterface
{
    protected $message;

    protected $subProcessStart;

    protected $interrupting = true;

    public function __construct(string $message, bool $subProcessStart = false)
    {
        $this->message = $message;
        $this->subProcessStart = $subProcessStart;
    }

    public function getMessageName(): string
    {
        return $this->message;
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
        if ($signal !== $this->message) {
            throw new \RuntimeException(\sprintf('Start event awaits message "%s", unable to process signal "%s"', $this->message, $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        $this->leave($execution);
    }

    /**
     * {@inheritdoc}
     */
    public function createEventSubscriptions(VirtualExecution $execution, string $activityId, ?Node $node = null): void
    {
        $execution->getEngine()->executeCommand(new CreateMessageSubscriptionCommand($this->message, $execution, $activityId, ($node === null) ? $execution->getNode() : $node));
    }
}
