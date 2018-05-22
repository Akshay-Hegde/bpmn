<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Delegate;

use KoolKode\Expression\ExpressionContextInterface;
use KoolKode\Util\UUID;

/**
 * Exposes an API for an execution to delegate task implementations, delegate tasks may read / write
 * execution / process data (variables, business key, etc) but must not take control of 
 * execution flow (transitions and signals).
 * 
 * @author Martin Schröder
 */
interface DelegateExecutionInterface
{
    /**
     * Get the unique identifier of the underlying execution.
     */
    public function getExecutionId(): UUID;

    /**
     * Get the ID of the activity being executed.
     */
    public function getActivityId(): ?string;

    /**
     * Get the unique identifier of the underlying process instance.
     */
    public function getProcessInstanceId(): UUID;

    /**
     * Get the ID of the process definition that defined the process being executed.
     */
    public function getProcessDefinitionId(): UUID;

    /**
     * Get the business key of the underlying process instance.
     */
    public function getBusinessKey(): ?string;

    /**
     * Check if the execution is a process instance.
     */
    public function isProcessInstance(): bool;

    /**
     * Check if the execution is active.
     */
    public function isActive(): bool;

    /**
     * Check if the execution is concurrent.
     */
    public function isConcurrent(): bool;

    /**
     * Check if the execution is a scope.
     */
    public function isScope(): bool;

    /**
     * Check if the execution is a scope root.
     */
    public function isScopeRoot(): bool;

    /**
     * Check if the execution is waiting for a signal.
     */
    public function isWaiting(): bool;

    /**
     * Create an expression context bound to the underlying execution.
     */
    public function getExpressionContext(): ExpressionContextInterface;

    /**
     * Check for existance of the given variabel in the current scope.
     */
    public function hasVariable(string $name): bool;

    /**
     * Get the value of the given variable from the current scope.
     */
    public function getVariable(string $name);

    /**
     * Set the value of the given variable in the current scope.
     */
    public function setVariable(string $name, $value): void;

    /**
     * Remove the given variable from the current scope.
     */
    public function removeVariable(string $name): void;
}
