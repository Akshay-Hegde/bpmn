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

class HistoricActivityInstance implements \JsonSerializable
{
	protected $id;
	
	protected $executionId;
	
	protected $processDefinitionId;
	
	protected $processDefinitionKey;
	
	protected $definitionKey;
	
	protected $startedAt;
	
	protected $endedAt;
	
	protected $duration;
	
	protected $completed = false;
	
	public function __construct(UUID $id, UUID $executionId, UUID $processDefinitionId, $processDefinitionKey, $definitionKey, \DateTimeInterface $startedAt)
	{
		$this->id = $id;
		$this->executionId = $executionId;
		$this->processDefinitionId = $processDefinitionId;
		$this->processDefinitionKey = (string)$processDefinitionKey;
		$this->definitionKey = (string)$definitionKey;
		$this->startedAt = clone $startedAt;
	}
	
	public function jsonSerialize()
	{
		return [
			'id' => $this->id,
			'executionId' => $this->executionId,
			'definitionKey' => $this->definitionKey,
			'startedAt' => $this->startedAt->format(\DateTime::ISO8601),
			'endetAt' => ($this->endedAt === NULL) ? NULL : $this->endedAt->format(\DateTime::ISO8601),
			'duration' => $this->duration
		];
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getExecutionId()
	{
		return $this->executionId;
	}
	
	public function getProcessDefinitionId()
	{
		return $this->processDefinitionId;
	}
	
	public function getProcessDefinitionKey()
	{
		return $this->processDefinitionKey;
	}
	
	public function getDefinitionKey()
	{
		return $this->definitionKey;
	}
	
	public function getStartedAt()
	{
		return clone $this->startedAt;
	}
	
	public function getEndedAt()
	{
		return ($this->endedAt === NULL) ? NULL : clone $this->endedAt;
	}
	
	public function setEndedAt(\DateTimeInterface $endedAt = NULL)
	{
		$this->endedAt = ($endedAt === NULL) ? NULL : clone $endedAt;
	}
	
	public function getDuration()
	{
		return $this->duration;
	}
	
	public function setDuration($duration = NULL)
	{
		$this->duration = ($duration === NULL) ? NULL : (float)$duration;
	}
	
	public function isCompleted()
	{
		return $this->completed;
	}
	
	public function setCompleted($completed)
	{
		$this->completed = $completed ? true : false;
	}
}
