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

use KoolKode\BPMN\Engine\AbstractBoundaryActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\CreateMessageSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Message catch event bound to an event scope.
 * 
 * @author Martin Schröder
 */
class MessageBoundaryEventBehavior extends AbstractBoundaryActivity
{
    protected $message;

    public function __construct(string $activityId, string $attachedTo, string $message)
    {
        parent::__construct($activityId, $attachedTo);
        
        $this->message = $message;
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        if ($signal !== $this->message) {
            throw new \RuntimeException(\sprintf('Boundary event awaits message "%s", unable to process signal "%s"', $this->message, $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        parent::processSignal($execution, $signal, $variables);
    }

    /**
     * {@inheritdoc}
     */
    public function createEventSubscriptions(VirtualExecution $execution, string $activityId, ?Node $node = null): void
    {
        $execution->getEngine()->executeCommand(new CreateMessageSubscriptionCommand($this->message, $execution, $activityId, $node, true));
    }
}
