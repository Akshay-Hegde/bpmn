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

class Job implements JobInterface
{
	protected $id;
	
	protected $executionId;
	
	protected $externalId;
	
	protected $handlerType;
	
	protected $handlerData;
	
	protected $retries;
	
	protected $lockOwner;
	
	protected $scheduledAt;
	
	protected $runAt;
	
	public function __construct(UUID $id, UUID $executionId, $handlerType, $handlerData, $retries = 0, $lockOwner = NULL)
	{
		$this->id = $id;
		$this->executionId = $executionId;
		$this->handlerType = (string)$handlerType;
		$this->handlerData = $handlerData;
		$this->retries = (int)$retries;
		$this->lockOwner = ($lockOwner === NULL) ? NULL : (string)$lockOwner;
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
		return $this->lockOwner !== NULL;
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
}
