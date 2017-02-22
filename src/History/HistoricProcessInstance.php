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

    public function __construct(UUID $id, UUID $processDefinitionId, $processDefinitionKey, $startActivityId, \DateTimeInterface $startedAt, array $variables = [])
    {
        $this->id = $id;
        $this->processDefinitionId = $processDefinitionId;
        $this->processDefinitionKey = (string) $processDefinitionKey;
        $this->startActivityId = (string) $startActivityId;
        $this->startedAt = clone $startedAt;
        $this->variables = $variables;
    }

    public function jsonSerialize()
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

    public function getId()
    {
        return $this->id;
    }

    public function getProcessDefinitionId()
    {
        return $this->processDefinitionId;
    }

    public function getProcessDefinitionKey()
    {
        return $this->processDefinitionKey;
    }

    public function getBusinessKey()
    {
        return $this->businessKey;
    }

    public function setBusinessKey($businessKey = null)
    {
        $this->businessKey = ($businessKey === null) ? null : (string) $businessKey;
    }

    public function getStartActivityId()
    {
        return $this->startActivityId;
    }

    public function getEndActivityId()
    {
        return $this->endActivityId;
    }

    public function setEndActivityId($endActivityId = null)
    {
        $this->endActivityId = ($endActivityId === null) ? null : (string) $endActivityId;
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

    public function isFinished()
    {
        return $this->endedAt !== null;
    }

    public function hasVariable($name)
    {
        return array_key_exists($name, $this->variables);
    }

    public function getVariable($name)
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }
        
        if (func_num_args() > 1) {
            return func_get_arg(1);
        }
        
        throw new \OutOfBoundsException(sprintf('Variable "%s" not defined in process instance %s', $name, $this->id));
    }

    public function getVariables()
    {
        return $this->variables;
    }
}
