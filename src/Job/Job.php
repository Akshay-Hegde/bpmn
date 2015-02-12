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
	
	protected $handlerType;
	
	protected $handlerData;
	
	protected $retries;
	
	protected $lockOwner;
	
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
	 * @return UUID
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @return UUID
	 */
	public function getExecutionId()
	{
		return $this->executionId;
	}
	
	public function getHandlerType()
	{
		return $this->handlerType;
	}
	
	public function getHandlerData()
	{
		return $this->handlerData;
	}
	
	public function getRetries()
	{
		return $this->retries;
	}
	
	public function isLocked()
	{
		return $this->lockOwner !== NULL;
	}
	
	public function getLockOwner()
	{
		return $this->lockOwner;
	}
}
