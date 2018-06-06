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

namespace KoolKode\BPMN\History;

use KoolKode\Util\UUID;

class HistoricActivityInstance implements \JsonSerializable
{
    protected $id;

    protected $processInstanceId;

    protected $processDefinitionId;

    protected $processDefinitionKey;

    protected $definitionKey;
    
    protected $name;

    protected $startedAt;

    protected $endedAt;

    protected $duration;

    protected $completed = false;

    public function __construct(UUID $id, UUID $processInstanceId, UUID $processDefinitionId, string $processDefinitionKey, string $definitionKey, string $name, \DateTimeImmutable $startedAt)
    {
        $this->id = $id;
        $this->processInstanceId = $processInstanceId;
        $this->processDefinitionId = $processDefinitionId;
        $this->processDefinitionKey = $processDefinitionKey;
        $this->definitionKey = $definitionKey;
        $this->name = $name;
        $this->startedAt = $startedAt;
    }

    public function jsonSerialize(): array
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

    public function getId(): UUID
    {
        return $this->id;
    }

    public function getProcessInstanceId(): UUID
    {
        return $this->processInstanceId;
    }

    public function getProcessDefinitionId(): UUID
    {
        return $this->processDefinitionId;
    }

    public function getProcessDefinitionKey(): string
    {
        return $this->processDefinitionKey;
    }

    public function getDefinitionKey(): string
    {
        return $this->definitionKey;
    }
    
    public function getName(): string
    {
        return $this->name;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): void
    {
        $this->endedAt = $endedAt;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(?float $duration): void
    {
        $this->duration = $duration;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): void
    {
        $this->completed = $completed;
    }
}
