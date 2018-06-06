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

class HistoricProcessInstance implements \JsonSerializable
{
    protected $id;

    protected $processDefinitionId;

    protected $processDefinitionKey;

    protected $businessKey;

    protected $startActivityId;

    protected $endActivityId;

    protected $startedAt;

    protected $endedAt;

    protected $duration;

    protected $variables;

    public function __construct(UUID $id, UUID $processDefinitionId, string $processDefinitionKey, string $startActivityId, \DateTimeImmutable $startedAt, array $variables = [])
    {
        $this->id = $id;
        $this->processDefinitionId = $processDefinitionId;
        $this->processDefinitionKey = $processDefinitionKey;
        $this->startActivityId = $startActivityId;
        $this->startedAt = clone $startedAt;
        $this->variables = $variables;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'processDefinitionId' => $this->processDefinitionId,
            'processDefinitionKey' => $this->processDefinitionKey,
            'businessKey' => $this->businessKey,
            'startActivityId' => $this->startActivityId,
            'endActivityId' => $this->endActivityId,
            'startedAt' => $this->startedAt->format(\DateTime::ISO8601),
            'endedAt' => ($this->endedAt === null) ? null : $this->endedAt->format(\DateTime::ISO8601),
            'duration' => $this->duration,
            'finished' => $this->isFinished()
        ];
    }

    public function getId(): UUID
    {
        return $this->id;
    }

    public function getProcessDefinitionId(): UUID
    {
        return $this->processDefinitionId;
    }

    public function getProcessDefinitionKey(): string
    {
        return $this->processDefinitionKey;
    }

    public function getBusinessKey(): ?string
    {
        return $this->businessKey;
    }

    public function setBusinessKey(?string $businessKey): void
    {
        $this->businessKey = $businessKey;
    }

    public function getStartActivityId(): string
    {
        return $this->startActivityId;
    }

    public function getEndActivityId(): ?string
    {
        return $this->endActivityId;
    }

    public function setEndActivityId(?string $endActivityId): void
    {
        $this->endActivityId = $endActivityId;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return clone $this->startedAt;
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

    public function isFinished(): bool
    {
        return $this->endedAt !== null;
    }

    public function hasVariable(string $name): bool
    {
        return \array_key_exists($name, $this->variables);
    }

    public function getVariable(string $name)
    {
        if (\array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }
        
        if (\func_num_args() > 1) {
            return \func_get_arg(1);
        }
        
        throw new \OutOfBoundsException(\sprintf('Variable "%s" not defined in process instance %s', $name, $this->id));
    }

    public function getVariables(): array
    {
        return $this->variables;
    }
}
