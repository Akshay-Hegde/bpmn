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

namespace KoolKode\BPMN\Task;

use KoolKode\Util\UUID;

/**
 * Represents a user task instance.
 * 
 * @author Martin Schröder
 */
interface TaskInterface
{
    /**
     * Get the unique identifier of this user task instance.
     */
    public function getId(): UUID;

    /**
     * Get the unique identifier of the execution that triggered the task instance.
     */
    public function getExecutionId(): ?UUID;

    /**
     * Get the process instance ID that create the task.
     */
    public function getProcessInstanceId(): ?UUID;

    /**
     * Get the business key of the process that spawned the task.
     */
    public function getProcessBusinessKey(): ?string;

    /**
     * Get the name (as defined in a BPMN 2.0 process diagram) of the activity to be performed.
     */
    public function getName(): string;

    /**
     * Get the documentation of the task (will contain text-only).
     */
    public function getDocumentation(): string;

    /**
     * Get the identifier (as defined by the "id" attribute in a BPMN 2.0 diagram) of the activity to be performed.
     */
    public function getDefinitionKey(): ?string;

    /**
     * Get the time of creation of this activity instance.
     */
    public function getCreated(): \DateTimeImmutable;

    /**
     * Check if the task has been claimed.
     */
    public function isClaimed(): bool;

    /**
     * Get the assignment date of this task.
     */
    public function getClaimDate(): ?\DateTimeImmutable;

    /**
     * Get the identity of the assignee of this task.
     */
    public function getAssignee(): ?string;

    /**
     * Get the task priority, defaults to 0.
     */
    public function getPriority(): int;

    /**
     * Check if the task has a due date set.
     */
    public function hasDueDate(): bool;

    /**
     * Get the due date of the task.
     */
    public function getDueDate(): ?\DateTimeImmutable;
}
