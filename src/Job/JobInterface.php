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

interface JobInterface
{
	/**
	 * @return UUID
	 */
	public function getId();
	
	/**
	 * @return UUID
	 */
	public function getExecutionId();
	
	/**
	 * Get external ID, that is an ID used by a system like a queue to trace messages.
	 * 
	 * @return string Or NULL when no such ID exists.
	 */
	public function getExternalId();
	
	public function getHandlerType();
	
	public function getRetries();
	
	public function isLocked();
	
	public function getLockOwner();
	
	public function getCreatedAt();
	
	public function getScheduledAt();
	
	public function getRunAt();
	
	public function getLockedAt();
	
	public function isFailed();
	
	public function getExceptionType();
	
	public function getExceptionMessage();
	
	public function getExceptionData();
}
