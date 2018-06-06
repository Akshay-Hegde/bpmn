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

namespace KoolKode\BPMN\Delegate;

use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Expression\ExpressionContextInterface;
use KoolKode\Util\UUID;

class DelegateExecution implements DelegateExecutionInterface
{
    protected $execution;

    public function __construct(VirtualExecution $execution)
    {
        $this->execution = $execution;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionId(): UUID
    {
        return $this->execution->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId(): ?string
    {
        $node = $this->execution->getNode();
        
        return ($node === null) ? null : $node->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessInstanceId(): UUID
    {
        return $this->execution->getRootExecution()->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessDefinitionId(): UUID
    {
        return new UUID($this->execution->getProcessModel()->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function isProcessInstance(): bool
    {
        return $this->execution->isRootExecution();
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return $this->execution->isActive();
    }

    /**
     * {@inheritdoc}
     */
    public function isConcurrent(): bool
    {
        return $this->execution->isConcurrent();
    }

    /**
     * {@inheritdoc}
     */
    public function isScope(): bool
    {
        return $this->execution->isScope();
    }

    /**
     * {@inheritdoc}
     */
    public function isScopeRoot(): bool
    {
        return $this->execution->isScopeRoot();
    }

    /**
     * {@inheritdoc}
     */
    public function isWaiting(): bool
    {
        return $this->execution->isWaiting();
    }

    /**
     * {@inheritdoc}
     */
    public function getBusinessKey(): ?string
    {
        return $this->execution->getBusinessKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpressionContext(): ExpressionContextInterface
    {
        return $this->execution->getExpressionContext();
    }

    /**
     * {@inheritdoc}
     */
    public function hasVariable(string $name): bool
    {
        return $this->execution->hasVariable($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariable(string $name)
    {
        if (\func_num_args() > 1) {
            return $this->execution->getVariable($name, \func_get_arg(1));
        }
        
        return $this->execution->getVariable($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setVariable(string $name, $value): void
    {
        $this->execution->setVariable($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function removeVariable(string $name): void
    {
        $this->execution->removeVariable($name);
    }
}
