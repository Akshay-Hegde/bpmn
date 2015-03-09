<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Job;

use KoolKode\Util\UUID;

class Job implements JobInterface, \JsonSerializable
{
	protected $id;
	
	protected $executionId;
	
	protected $externalId;
	
	protected $handlerType;
	
	protected $handlerData;
	
	protected $retries;
	
	protected $locked = false;
	
	protected $lockOwner;
	
	protected $createdAt;
	
	protected $scheduledAt;
	
	protected $lockedAt;
	
	protected $runAt;
	
	protected $exceptionType;
	
	protected $exceptionMessage;
	
	protected $exceptionData;
	
	public function __construct(UUID $id, UUID $executionId, $handlerType, $handlerData, \DateTimeInterface $createdAt = NULL, $retries = 0, $lockOwner = NULL)
	{
		$this->id = $id;
		$this->executionId = $executionId;
		$this->handlerType = (string)$handlerType;
		$this->handlerData = $handlerData;
		$this->createdAt = ($createdAt === NULL) ? new \DateTimeImmutable('now') : new \DateTimeImmutable('@' . $createdAt->getTimestamp());
		$this->retries = (int)$retries;
		$this->lockOwner = ($lockOwner === NULL) ? NULL : (string)$lockOwner;
	}
	
	public function jsonSerialize()
	{
		return [
			'id' => $this->id,
			'execution_id' => $this->executionId,
			'externalId' => $this->executionId,
			'handlerType' => $this->handlerType,
			'retries' => $this->retries,
			'scheduledAt' => ($this->scheduledAt === NULL) ? NULL : $this->scheduledAt->format(\DateTime::ISO8601),
			'runAt' => ($this->runAt === NULL) ? NULL : $this->runAt->format(\DateTime::ISO8601)
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
	
	/**
	 * {@inheritdoc}
	 */
	public function getExternalId()
	{
		return $this->externalId;
	}
	
	public function setExternalId($id = NULL)
	{
		$this->externalId = ($id === NULL) ? NULL : (string)$id;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getHandlerType()
	{
		return $this->handlerType;
	}
	
	public function getHandlerData()
	{
		return $this->handlerData;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getRetries()
	{
		return $this->retries;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function isLocked()
	{
		return $this->locked;
	}
	
	public function setLocked($locked)
	{
		$this->locked = $locked ? true : false;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getLockOwner()
	{
		return $this->lockOwner;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getCreatedAt()
	{
		return $this->createdAt;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getScheduledAt()
	{
		return $this->scheduledAt;
	}
	
	public function setScheduledAt(\DateTimeInterface $scheduledAt = NULL)
	{
		if($scheduledAt === NULL)
		{
			$this->scheduledAt = NULL;
		}
		else
		{
			$this->scheduledAt = new \DateTimeImmutable('@' . $scheduledAt->getTimestamp(), new \DateTimeZone('UTC'));
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getRunAt()
	{
		return $this->runAt;
	}
	
	public function setRunAt(\DateTimeInterface $runAt = NULL)
	{
		if($runAt === NULL)
		{
			$this->runAt = NULL;
		}
		else
		{
			$this->runAt = new \DateTimeImmutable('@' . $runAt->getTimestamp(), new \DateTimeZone('UTC'));
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getLockedAt()
	{
		return $this->lockedAt;
	}
	
	public function setLockedAt(\DateTimeInterface $lockedAt = NULL)
	{
		if($lockedAt === NULL)
		{
			$this->lockedAt = NULL;
		}
		else
		{
			$this->lockedAt = new \DateTimeImmutable('@' . $lockedAt->getTimestamp(), new \DateTimeZone('UTC'));
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function isFailed()
	{
		return $this->exceptionType !== NULL;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getExceptionType()
	{
		return $this->exceptionType;
	}
	
	public function setExceptionType($exceptionType = NULL)
	{
		$this->exceptionType = ($exceptionType === NULL) ? NULL : (string)$exceptionType;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getExceptionMessage()
	{
		return $this->exceptionMessage;
	}
	
	public function setExceptionMessage($exceptionMessage = NULL)
	{
		$this->exceptionMessage = ($exceptionMessage === NULL) ? NULL : (string)$exceptionMessage;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function getExceptionData()
	{
		return $this->exceptionData;
	}
	
	public function setExceptionData($exceptionData = NULL)
	{
		$this->exceptionData = ($exceptionData === NULL) ? NULL : (string)$exceptionData;
	}
}
