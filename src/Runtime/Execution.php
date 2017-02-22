<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Runtime;

use KoolKode\BPMN\Repository\ProcessDefinition;
use KoolKode\Util\UUID;
use KoolKode\BPMN\Engine\VirtualExecution;

class Execution implements ExecutionInterface, \JsonSerializable
{
    protected $id;

    protected $parentId;

    protected $processInstanceId;

    protected $definition;

    protected $activityId;

    protected $state;

    protected $businessKey;

    public function __construct(ProcessDefinition $definition, UUID $id, UUID $processInstanceId, UUID $parentId = null, $activityId = null, $state = 0, $businessKey = null)
    {
        $this->definition = $definition;
        $this->id = $id;
        $this->parentId = $parentId;
        $this->processInstanceId = $processInstanceId;
        $this->activityId = ($activityId === null) ? null : (string) $activityId;
        $this->state = (int) $state;
        $this->businessKey = ($businessKey === null) ? null : (string) $businessKey;
    }

    public function jsonSerialize()
    {
        return [
            'id' => (string) $this->id,
            'parentId' => ($this->parentId === null) ? null : (string) $this->parentId,
            'processInstanceId' => ($this->processInstanceId === null) ? null : (string) $this->processInstanceId,
            'processDefinitionId' => (string) $this->definition->getId(),
            'processDefinitionKey' => $this->definition->getKey(),
            'processDefinitionRevision' => $this->definition->getRevision(),
            'activityId' => $this->activityId,
            'businessKey' => $this->businessKey,
            'ended' => $this->isEnded()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isProcessInstance()
    {
        return $this->id == $this->processInstanceId;
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
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessInstanceId()
    {
        return $this->processInstanceId;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessDefinition()
    {
        return $this->definition;
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId()
    {
        return $this->activityId;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return 0 != ($this->state & VirtualExecution::STATE_ACTIVE);
    }

    /**
     * {@inheritdoc}
     */
    public function isConcurrent()
    {
        return 0 != ($this->state & VirtualExecution::STATE_CONCURRENT);
    }

    /**
     * {@inheritdoc}
     */
    public function isScope()
    {
        return 0 != ($this->state & VirtualExecution::STATE_SCOPE);
    }

    /**
     * {@inheritdoc}
     */
    public function isScopeRoot()
    {
        return 0 != ($this->state & VirtualExecution::STATE_SCOPE_ROOT);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnded()
    {
        return 0 != ($this->state & VirtualExecution::STATE_TERMINATE);
    }

    /**
     * {@inheritdoc}
     */
    public function isWaiting()
    {
        return 0 != ($this->state & VirtualExecution::STATE_WAIT);
    }

    /**
     * {@inheritdoc}
     */
    public function getBusinessKey()
    {
        return $this->businessKey;
    }
}
