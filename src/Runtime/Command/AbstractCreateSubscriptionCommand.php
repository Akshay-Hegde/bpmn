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

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Job\Job;
use KoolKode\Process\Node;
use KoolKode\Util\UUID;

/**
 * Base class for event subscription commands.
 * 
 * @author Martin Schröder
 */
abstract class AbstractCreateSubscriptionCommand extends AbstractBusinessCommand
{
    /**
     * Name of the subscription type: "signal", "message", etc.
     * 
     * @var string
     */
    protected $name;

    /**
     * ID of the target execution.
     * 
     * @var UUID
     */
    protected $executionId;

    /**
     * ID of the activity that created the event subscription.
     * 
     * @var string
     */
    protected $activityId;

    /**
     * ID of the target node to receive the delegated signal or null in order to use the activity node.
     * 
     * @var string
     */
    protected $nodeId;

    /**
     * Is this a subscription for a boundary event?
     * 
     * @var boolean
     */
    protected $boundaryEvent;

    /**
     * Create a new persisted event subscription.
     * 
     * @param string $name Name of the subscription type: "signal", "message", etc.
     * @param VirtualExecution $execution Target execution.
     * @param string $activityId ID of the activity that created the event subscription.
     * @param Node $node Target node to receive the delegated signal or null in order to use the activity node.
     * @param boolean $boundaryEvent Is this a subscription for a boundary event?
     */
    public function __construct(string $name, VirtualExecution $execution, string $activityId, ?Node $node = null, ?bool $boundaryEvent = false)
    {
        $this->name = $name;
        $this->executionId = $execution->getId();
        $this->activityId = $activityId;
        $this->nodeId = ($node === null) ? null : (string) $node->getId();
        $this->boundaryEvent = $boundaryEvent;
    }

    /**
     * {@inheritdoc}
     * 
     * @codeCoverageIgnore
     */
    public function isSerializable(): bool
    {
        return true;
    }

    /**
     * Create an event subscription entry in the DB.
     */
    protected function createSubscription(ProcessEngine $engine, ?Job $job = null): void
    {
        $execution = $engine->findExecution($this->executionId);
        $nodeId = ($this->nodeId === null) ? null : $execution->getProcessModel()->findNode($this->nodeId)->getId();
        
        $data = [
            'id' => UUID::createRandom(),
            'execution_id' => $execution->getId(),
            'activity_id' => $this->activityId,
            'node' => $nodeId,
            'process_instance_id' => $execution->getRootExecution()->getId(),
            'flags' => $this->getSubscriptionFlag(),
            'boundary' => $this->boundaryEvent ? 1 : 0,
            'name' => $this->name,
            'created_at' => \time()
        ];
        
        if ($job !== null) {
            $data['job_id'] = $job->getId();
        }
        
        $engine->getConnection()->insert('#__bpmn_event_subscription', $data);
    }

    /**
     * Get the value being used as flag in the subscription table.
     */
    protected abstract function getSubscriptionFlag(): int;
}
