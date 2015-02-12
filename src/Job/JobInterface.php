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
	
	public function getHandlerType();
	
	public function getRetries();
	
	public function isLocked();
	
	public function getLockOwner();
}
