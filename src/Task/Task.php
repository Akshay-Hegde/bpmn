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

    public function __construct(UUID $id, $name, \DateTimeImmutable $created, \DateTimeImmutable $claimDate = null, $assignee = null, $priority = 0, \DateTimeImmutable $dueDate = null)
    {
        $this->id = $id;
        $this->name = (string) $name;
        $this->created = $created;
        $this->claimDate = $claimDate;
        $this->assignee = ($assignee === null) ? null : (string) $assignee;
        $this->priority = (int) $priority;
        $this->dueDate = $dueDate;
    }

    public function jsonSerialize()
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionId()
    {
        return $this->executionId;
    }

    public function setExecutionId(UUID $executionId = null)
    {
        $this->executionId = $executionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessInstanceId()
    {
        return $this->processInstanceId;
    }

    public function setProcessInstanceId(UUID $processInstanceId = null)
    {
        $this->processInstanceId = $processInstanceId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessBusinessKey()
    {
        return $this->processBusinessKey;
    }

    public function setProcessBusinessKey($processBusinessKey)
    {
        $this->processBusinessKey = ($processBusinessKey === null) ? null : (string) $processBusinessKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentation()
    {
        return $this->documentation;
    }

    public function setDocumentation($documentation = null)
    {
        $this->documentation = trim($documentation);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinitionKey()
    {
        return $this->definitionKey;
    }

    public function setDefinitionKey($definitionKey = null)
    {
        $this->definitionKey = ($definitionKey === null) ? null : (string) $definitionKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * {@inheritdoc}
     */
    public function isClaimed()
    {
        return $this->claimDate !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getClaimDate()
    {
        return $this->claimDate;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function hasDueDate()
    {
        return $this->dueDate !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDueDate()
    {
        return $this->dueDate;
    }
}
