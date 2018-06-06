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

namespace KoolKode\BPMN\Runtime;

use KoolKode\Util\UUID;

/**
 * Persisted event subscription, these subscriptions are allways related to a single execution.
 * 
 * @author Martin Schröder
 */
class EventSubscription implements \JsonSerializable
{
    const TYPE_SIGNAL = 1;

    const TYPE_MESSAGE = 2;

    const TYPE_TIMER = 3;

    protected $id;

    protected $executionId;

    protected $processInstanceId;

    protected $activityId;

    protected $eventType;

    protected $eventName;

    protected $created;

    protected $jobId;

    protected $boundaryEvent;

    public function __construct(UUID $id, UUID $executionId, UUID $processInstanceId, string $activityId, int $eventType, string $eventName, \DateTimeImmutable $created)
    {
        $this->id = $id;
        $this->executionId = $executionId;
        $this->processInstanceId = $processInstanceId;
        $this->activityId = $activityId;
        $this->eventType = $eventType;
        $this->eventName = $eventName;
        $this->created = $created;
    }

    public function jsonSerialize()
    {
        return [
            'id' => (string) $this->id,
            'executionId' => (string) $this->executionId,
            'processInstanceId' => (string) $this->processInstanceId,
            'activityId' => $this->activityId,
            'eventType' => $this->eventType,
            'eventName' => $this->eventName,
            'created' => $this->created->format(\DateTime::ISO8601)
        ];
    }

    /**
     * Get the unique event subscription ID.
     * 
     * @return UUID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the ID of the execution that created the subscription.
     * 
     * @return UUID
     */
    public function getExecutionId()
    {
        return $this->executionId;
    }

    /**
     * Get the process instance ID of the process that created the subscription.
     * 
     * @return UUID
     */
    public function getProcessInstanceId()
    {
        return $this->processInstanceId;
    }

    /**
     * Get the definition key of the activity that created the event subscription.
     * 
     * @return string
     */
    public function getActivityId()
    {
        return $this->activityId;
    }

    /**
     * Check if the subscription is a signal event.
     * 
     * @return boolean
     */
    public function isSignal()
    {
        return $this->eventType == self::TYPE_SIGNAL;
    }

    /**
     * Check if the subscription is a message event.
     * 
     * @return boolean
     */
    public function isMessage()
    {
        return $this->eventType == self::TYPE_MESSAGE;
    }

    /**
     * Check if the subscription is a timer event.
     * 
     * @return boolean
     */
    public function isTimer()
    {
        return $this->eventType == self::TYPE_TIMER;
    }

    /**
     * Get the event type, one of the EventSubscription::TYPE_* constants.
     * 
     * @return integer
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * Get the name of the event (signal / message name).
     * 
     * @return string
     */
    public function getEventName()
    {
        return $this->eventName;
    }

    /**
     * Get date and time of the creation of this event subscription.
     * 
     * @return \DateTimeImmutable
     */
    public function getCreated()
    {
        return $this->created;
    }

    public function hasJob()
    {
        return $this->jobId !== null;
    }

    /**
     * Get the ID of the job associated with the event (only relevant when the event is a timer event).
     * 
     * @return UUID
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    public function setJobId(UUID $jobId = null)
    {
        $this->jobId = $jobId;
    }

    public function isBoundaryEvent()
    {
        return $this->boundaryEvent;
    }

    public function setBoundaryEvent($boundaryEvent)
    {
        $this->boundaryEvent = $boundaryEvent ? true : false;
    }
}
