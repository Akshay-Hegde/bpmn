<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Task;

use KoolKode\Util\UUID;

class Task implements TaskInterface, \JsonSerializable
{
    protected $id;

    protected $executionId;

    protected $processInstanceId;

    protected $processBusinessKey;

    protected $name;

    protected $definitionKey;

    protected $created;

    protected $claimDate;

    protected $assignee;

    protected $priority;

    protected $dueDate;

    protected $documentation = '';

    public function __construct(UUID $id, string $name, \DateTimeImmutable $created, ?\DateTimeImmutable $claimDate = null, ?string $assignee = null, int $priority = 0, ?\DateTimeImmutable $dueDate = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->created = $created;
        $this->claimDate = $claimDate;
        $this->assignee = $assignee;
        $this->priority = $priority;
        $this->dueDate = $dueDate;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => (string) $this->id,
            'executionId' => (string) $this->executionId,
            'processInstanceId' => $this->processInstanceId,
            'processBusinessKey' => $this->processBusinessKey,
            'name' => $this->name,
            'definitionKey' => $this->definitionKey,
            'assignee' => $this->assignee,
            'creationDate' => $this->created->format(\DateTime::ISO8601),
            'claimDate' => ($this->claimDate === null) ? null : $this->claimDate->format(\DateTime::ISO8601),
            'priority' => $this->priority,
            'dueDate' => ($this->dueDate === null) ? null : $this->dueDate->format(\DateTime::ISO8601)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): UUID
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionId(): ?UUID
    {
        return $this->executionId;
    }

    public function setExecutionId(?UUID $executionId): void
    {
        $this->executionId = $executionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessInstanceId(): ?UUID
    {
        return $this->processInstanceId;
    }

    public function setProcessInstanceId(?UUID $processInstanceId): void
    {
        $this->processInstanceId = $processInstanceId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessBusinessKey(): ?string
    {
        return $this->processBusinessKey;
    }

    public function setProcessBusinessKey(?string $processBusinessKey): void
    {
        $this->processBusinessKey = $processBusinessKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentation(): string
    {
        return $this->documentation;
    }

    public function setDocumentation(string $documentation): void
    {
        $this->documentation = \trim($documentation);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionKey(): ?string
    {
        return $this->definitionKey;
    }

    public function setDefinitionKey(?string $definitionKey): void
    {
        $this->definitionKey = $definitionKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function isClaimed(): bool
    {
        return $this->claimDate !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getClaimDate(): ?\DateTimeImmutable
    {
        return $this->claimDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssignee(): ?string
    {
        return $this->assignee;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDueDate(): bool
    {
        return $this->dueDate !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }
}
