<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\History;

use KoolKode\Util\UUID;

class HistoricActivityInstance implements \JsonSerializable
{
    protected $id;

    protected $processInstanceId;

    protected $processDefinitionId;

    protected $processDefinitionKey;

    protected $definitionKey;

    protected $startedAt;

    protected $endedAt;

    protected $duration;

    protected $completed = false;

    public function __construct(UUID $id, UUID $processInstanceId, UUID $processDefinitionId, $processDefinitionKey, $definitionKey, \DateTimeInterface $startedAt)
    {
        $this->id = $id;
        $this->processInstanceId = $processInstanceId;
        $this->processDefinitionId = $processDefinitionId;
        $this->processDefinitionKey = (string) $processDefinitionKey;
        $this->definitionKey = (string) $definitionKey;
        $this->startedAt = clone $startedAt;
    }

    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'processInstanceId' => $this->processInstanceId,
            'processDefinitionId' => $this->processDefinitionId,
            'processDefinitionKey' => $this->processDefinitionKey,
            'definitionKey' => $this->definitionKey,
            'startedAt' => $this->startedAt->format(\DateTime::ISO8601),
            'endetAt' => ($this->endedAt === null) ? null : $this->endedAt->format(\DateTime::ISO8601),
            'duration' => $this->duration,
            'completed' => $this->completed
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProcessInstanceId()
    {
        return $this->processInstanceId;
    }

    public function getProcessDefinitionId()
    {
        return $this->processDefinitionId;
    }

    public function getProcessDefinitionKey()
    {
        return $this->processDefinitionKey;
    }

    public function getDefinitionKey()
    {
        return $this->definitionKey;
    }

    public function getStartedAt()
    {
        return clone $this->startedAt;
    }

    public function getEndedAt()
    {
        return ($this->endedAt === null) ? null : clone $this->endedAt;
    }

    public function setEndedAt(\DateTimeInterface $endedAt = null)
    {
        $this->endedAt = ($endedAt === null) ? null : clone $endedAt;
    }

    public function getDuration()
    {
        return $this->duration;
    }

    public function setDuration($duration = null)
    {
        $this->duration = ($duration === null) ? null : (float) $duration;
    }

    public function isCompleted()
    {
        return $this->completed;
    }

    public function setCompleted($completed)
    {
        $this->completed = $completed ? true : false;
    }
}
