<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Job\Executor;

use KoolKode\BPMN\Job\Handler\JobHandlerInterface;
use KoolKode\BPMN\Job\Job;
use KoolKode\Util\UUID;

/**
 * Contract for the BPMN job executor.
 * 
 * @author Martin Schröder
 */
interface JobExecutorInterface
{
	/**
	 * Schedule and remove jobs according to current state.
	 */
	public function syncScheduledJobs();
	
	/**
	 * Check if a job handler of the given type is registered.
	 * 
	 * @param string $type
	 * @return boolean
	 */
	public function hasJobHandler($type);
	
	/**
	 * Register a job handler with the executor.
	 * 
	 * @param JobHandlerInterface $handler
	 */
	public function registerJobHandler(JobHandlerInterface $handler);
	
	/**
	 * Schedule a job for execution.
	 *
	 * @param Job $job
	 */
	public function scheduleJob(UUID $executionId, $handlerType, $data, \DateTimeInterface $runAt = NULL);
	
	/**
	 * Execute the given job using the process engine.
	 * 
	 * @param Job $job
	 */
	public function executeJob(Job $job);
	
	/**
	 * Remove a job by ID.
	 * 
	 * @param UUID $jobId
	 */
	public function removeJob(UUID $jobId);
}
