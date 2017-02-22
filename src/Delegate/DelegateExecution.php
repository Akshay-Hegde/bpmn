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

use KoolKode\BPMN\Engine\VirtualExecution;
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
    public function getExecutionId()
    {
        return $this->execution->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId()
    {
        $node = $this->execution->getNode();
        
        return ($node === null) ? null : $node->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessInstanceId()
    {
        return $this->execution->getRootExecution()->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessDefinitionId()
    {
        return new UUID($this->execution->getProcessModel()->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function isProcessInstance()
    {
        return $this->execution->isRootExecution();
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return $this->execution->isActive();
    }

    /**
     * {@inheritdoc}
     */
    public function isConcurrent()
    {
        return $this->execution->isConcurrent();
    }

    /**
     * {@inheritdoc}
     */
    public function isScope()
    {
        return $this->execution->isScope();
    }

    /**
     * {@inheritdoc}
     */
    public function isScopeRoot()
    {
        return $this->execution->isScopeRoot();
    }

    /**
     * {@inheritdoc}
     */
    public function isWaiting()
    {
        return $this->execution->isWaiting();
    }

    /**
     * {@inheritdoc}
     */
    public function getBusinessKey()
    {
        return $this->execution->getBusinessKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getExpressionContext()
    {
        return $this->execution->getExpressionContext();
    }

    /**
     * {@inheritdoc}
     */
    public function hasVariable($name)
    {
        return $this->execution->hasVariable($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariable($name)
    {
        if (func_num_args() > 1) {
            return $this->execution->getVariable($name, func_get_arg(1));
        }
        
        return $this->execution->getVariable($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setVariable($name, $value)
    {
        $this->execution->setVariable($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function removeVariable($name)
    {
        $this->execution->removeVariable($name);
    }
}
