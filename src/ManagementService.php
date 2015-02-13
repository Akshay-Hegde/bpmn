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
	
	/**
	 * Create a new job query.
	 * 
	 * @return JobQuery
	 */
	public function createJobQuery()
	{
		return new JobQuery($this->engine);
	}
	
	public function executeJob(UUID $jobId)
	{
		// TODO: Job execution requires locking due to concurrency...
		
		$job = $this->createJobQuery()->jobId($jobId)->findOne();
		
		$this->engine->executeJob($job);
	}
	
	public function deleteJob(UUID $jobId)
	{
		$stmt = $this->engine->prepareQuery("DELETE FROM `#__bpmn_job` WHERE `id` = :id");
		$stmt->bindValue('id', $jobId);
		$stmt->execute();
	}
	
	public function setJobRetries(UUID $jobId, $retries)
	{
		$retries = (int)$retries;
		
		if($retries < 0)
		{
			throw new \InvalidArgumentException(sprintf('Job retry count must not be negative'));
		}
		
		$stmt = $this->engine->prepareQuery("UPDATE `#__bpmn_job` SET `retries` = :retries WHERE `id` = :id");
		$stmt->bindValue('retries', $retries);
		$stmt->bindValue('id', $jobId);
		$stmt->execute();
	}
}
