<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Job\Scheduler;

use KoolKode\BPMN\Job\Job;
use KoolKode\Util\UUID;

/**
 * Contract for a BPMN job scheduler.
 * 
 * @author Martin Schröder
 */
interface JobSchedulerInterface
{
	/**
	 * Schedule a job for execution.
	 * 
	 * @param Job $job
	 */
	public function scheduleJob(Job $job);
	
	/**
	 * Remove a job from the schedule.
	 * 
	 * @param UUID $jobId
	 */
	public function removeJob(UUID $jobId);
}
