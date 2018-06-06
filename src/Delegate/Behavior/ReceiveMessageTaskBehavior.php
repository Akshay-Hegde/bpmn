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

namespace KoolKode\BPMN\Delegate\Behavior;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Behavior\IntermediateCatchEventInterface;
use KoolKode\BPMN\Runtime\Command\CreateMessageSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Receive task that waits for arrival of a message.
 * 
 * @author Martin Schröder
 */
class ReceiveMessageTaskBehavior extends AbstractScopeActivity implements IntermediateCatchEventInterface
{
    protected $message;

    public function __construct(string $activityId, string $message)
    {
        parent::__construct($activityId);
        
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
            throw new \RuntimeException(\sprintf('Receive task awaits message "%s", unable to process signal "%s"', $this->message, $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        $engine = $execution->getEngine();
        $name = $this->getStringValue($this->name, $execution->getExpressionContext());
        
        $engine->debug('Receive task "{task}" triggered by message <{message}>', [
            'task' => $name,
            'message' => $signal
        ]);
        
        $engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
        
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
