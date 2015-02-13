<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Job\JobQuery;
use KoolKode\Util\UUID;

class ManagementService
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function createJobQuery()
	{
		return new JobQuery($this->engine);
	}
	
	public function executeJob(UUID $jobId)
	{
		$job = $this->createJobQuery()->jobId($jobId)->findOne();
		
		$this->engine->executeJob($job);
	}
	
	public function deleteJob(UUID $jobId)
	{
		
	}
	
	public function setJobRetries(UUID $jobId, $retries)
	{
		
	}
}
