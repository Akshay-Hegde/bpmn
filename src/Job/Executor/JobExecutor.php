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

use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Job\Handler\JobHandlerInterface;
use KoolKode\BPMN\Job\Job;
use KoolKode\BPMN\Job\Scheduler\JobSchedulerInterface;
use KoolKode\Util\UUID;

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
	
	protected $scheduledJobs = [];
	
	protected $removedJobs = [];
	
	/**
	 * Create a new job executor backed by the engine and the given job scheduler.
	 * 
	 * @param ProcessEngine $engine
	 * @param JobSchedulerInterface $scheduler
	 */
	public function __construct(ProcessEngine $engine, JobSchedulerInterface $scheduler = NULL)
	{
		$this->engine = $engine;
		$this->scheduler = $scheduler;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function syncScheduledJobs()
	{
		while(!empty($this->removedJobs))
		{
			$job = array_shift($this->removedJobs);
			
			if($this->scheduler !== NULL)
			{
				$this->scheduler->removeJob($job);
			}
		}
		
		while(!empty($this->scheduledJobs))
		{
			$job = array_shift($this->scheduledJobs);
			
			if($this->scheduler !== NULL)
			{
				$this->scheduler->scheduleJob($job);
				
				continue;
			}
			
			if($job->getRunAt() === NULL)
			{
				$this->engine->warning('Cannot schedule job of type "{handler}" within {execution} to run immediately', [
					'handler' => $job->getHandlerType(),
					'execution' => (string)$job->getExecutionId()
				]);
			}
			else
			{
				$this->engine->warning('Cannot schedule job of type "{handler}" within {execution} to run at {scheduled}', [
					'handler' => $job->getHandlerType(),
					'execution' => (string)$job->getExecutionId(),
					'scheduled' => $job->getRunAt()->format(\DateTime::ISO8601)
				]);
			}
		}
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
	public function hasJobScheduler()
	{
		return $this->scheduler !== NULL;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function scheduleJob(UUID $executionId, $handlerType, $data, \DateTimeInterface $runAt = NULL)
	{
		$id = UUID::createRandom();
		$handlerType = (string)$handlerType;
		
		$job = new Job($id, $executionId, $handlerType, $data);
		$job->setRunAt($runAt);
		
		$time = $job->getRunAt();
		
		if($time !== NULL)
		{
			$time = $time->getTimestamp();
		}
		
		$stmt = $this->engine->prepareQuery("
			INSERT INTO `#__bpmn_job`
				(`id`, `execution_id`, `handler_type`, `handler_data`, `run_at`)
			VALUES
				(:id, :eid, :type, :data, :time)
		");
		$stmt->bindValue('id', $job->getId());
		$stmt->bindValue('eid', $job->getExecutionId());
		$stmt->bindValue('type', $job->getHandlerType());
		$stmt->bindValue('data', new BinaryData(serialize($job->getHandlerData())));
		$stmt->bindValue('time', $time);
		$stmt->execute();
		
		$this->engine->info('Scheduled job <{job}> of type "{handler}" relate to execution <{execution}>', [
			'job' => (string)$job->getId(),
			'handler' => $handlerType,
			'execution' => (string)$executionId
		]);
		
		return $this->scheduledJobs[] = $job;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function executeJob(Job $job)
	{
		$this->engine->executeCommand(new ExecuteJobCommand($job, $this->findJobHandler($job)));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function removeJob(UUID $jobId)
	{
		$stmt = $this->engine->prepareQuery("DELETE FROM `#__bpmn_job` WHERE `id` = :id");
		$stmt->bindValue('id', $jobId);
		$stmt->execute();
		
		$this->engine->info('Removed job <{job}>', [
			'job' => (string)$jobId
		]);
		
		$this->removedJobs[] = $jobId;
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
