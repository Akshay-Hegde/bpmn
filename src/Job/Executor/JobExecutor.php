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

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Job\Handler\JobHandlerInterface;
use KoolKode\BPMN\Job\Job;
use KoolKode\BPMN\Job\Scheduler\JobSchedulerInterface;

/**
 * Default implementation of a job executor that leverages a command and a job scheduler.
 * 
 * @author Martin Schröder
 */
class JobExecutor implements JobExecutorInterface
{
	/**
	 * @var ProcessEngine
	 */
	protected $engine;
	
	/**
	 * @var JobSchedulerInterface
	 */
	protected $scheduler;
	
	/**
	 * Registered job handlers grouped by job type.
	 * 
	 * @var array<string, JobHandlerInterface>
	 */
	protected $handlers = [];
	
	/**
	 * Create a new job executor backed by the engine and the given job scheduler.
	 * 
	 * @param ProcessEngine $engine
	 * @param JobSchedulerInterface $scheduler
	 */
	public function __construct(ProcessEngine $engine, JobSchedulerInterface $scheduler)
	{
		$this->engine = $engine;
		$this->scheduler = $scheduler;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function registerJobHandler(JobHandlerInterface $handler)
	{
		$this->handlers[$handler->getType()] = $handler;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function hasJobHandler($type)
	{
		return isset($this->handlers[(string)$type]);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function scheduleJob(Job $job)
	{
		$this->scheduler->scheduleJob($job);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function executeJob(Job $job)
	{
		$this->engine->executeCommand(new ExecuteJobCommand($job, $this->findJobHandler($job)));
	}
	
	/**
	 * Find a handler for the given job by matching the handler type value of the job.
	 * 
	 * @param Job $job
	 * @return JobHandlerInterface
	 * 
	 * @throws \OutOfBoundsException When no handler for the job could be resolved.
	 */
	protected function findJobHandler(Job $job)
	{
		foreach($this->handlers as $type => $handler)
		{
			if($job->getHandlerType() == $type)
			{
				return $handler;
			}
		}
		
		throw new \OutOfBoundsException(sprintf('Job handler "%s" not found for job %s', $job->getHandlerType(), $job->getId()));
	}
}
