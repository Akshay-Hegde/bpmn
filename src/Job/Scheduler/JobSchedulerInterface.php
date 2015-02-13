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

use KoolKode\BPMN\Job\Handler\JobHandlerInterface;
use KoolKode\BPMN\Job\Job;

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
}
