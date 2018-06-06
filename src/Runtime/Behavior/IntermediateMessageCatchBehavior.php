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
 * Subscribes to a message event and waits for message arrival.
 * 
 * @author Martin Schröder
 */
class IntermediateMessageCatchBehavior extends AbstractActivity implements IntermediateCatchEventInterface
{
    protected $message;

    public function __construct(string $message)
    {
        $this->message = $message;
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
        if ($signal !== $this->message) {
            throw new \RuntimeException(\sprintf('Catch event awaits message "%s", unable to process signal "%s"', $this->message, $signal));
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
