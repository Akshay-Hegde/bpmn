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
     * Unique ID of the job executor, used when locking jobs.
     * 
     * @var string
     */
    protected $lockOwner;

    /**
     * DB lock timeout in seconds.
     * 
     * @var integer
     */
    protected $lockTimeout = 30;

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
    public function __construct(ProcessEngine $engine, JobSchedulerInterface $scheduler = null)
    {
        $this->engine = $engine;
        $this->scheduler = $scheduler;
        
        $this->lockOwner = (string) UUID::createRandom();
    }

    /**
     * {@inheritdoc}
     */
    public function getLockOwner()
    {
        return $this->getLockOwner();
    }

    public function setLockOwner($owner)
    {
        $this->lockOwner = (string) $owner;
    }

    /**
     * {@inheritdoc}
     */
    public function getLockTimeout()
    {
        return $this->lockTimeout;
    }

    public function setLockTimeout($timeout)
    {
        $ttl = (int) $timeout;
        
        if ($ttl < 5) {
            throw new \InvalidArgumentException(sprintf('Lock timout must not be less than 5 seconds, given %s', $timeout));
        }
        
        if ($ttl > 1800) {
            throw new \InvalidArgumentException(sprintf('Lock timout must not be more than 30 minutes, given %s', $timeout));
        }
        
        $this->lockTimeout = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function syncScheduledJobs()
    {
        while (!empty($this->removedJobs)) {
            $job = array_shift($this->removedJobs);
            
            if ($this->scheduler !== null) {
                $this->scheduler->removeJob($job);
            }
        }
        
        while (!empty($this->scheduledJobs)) {
            $job = array_shift($this->scheduledJobs);
            
            if ($this->scheduler !== null) {
                $this->scheduler->scheduleJob($job);
                
                continue;
            }
            
            if ($job->getRunAt() === null) {
                $this->engine->warning('Cannot schedule job of type "{handler}" within {execution} to run immediately', [
                    'handler' => $job->getHandlerType(),
                    'execution' => (string) $job->getExecutionId()
                ]);
            } else {
                $this->engine->warning('Cannot schedule job of type "{handler}" within {execution} to run at {scheduled}', [
                    'handler' => $job->getHandlerType(),
                    'execution' => (string) $job->getExecutionId(),
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
        return isset($this->handlers[(string) $type]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasJobScheduler()
    {
        return $this->scheduler !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function scheduleJob(UUID $executionId, $handlerType, $data, \DateTimeImmutable $runAt = null)
    {
        $id = UUID::createRandom();
        $handlerType = (string) $handlerType;
        
        $job = new Job($id, $executionId, $handlerType, $data);
        $job->setRunAt($runAt);
        
        $time = $job->getRunAt();
        
        if ($time !== null) {
            $time = $time->getTimestamp();
        }
        
        $this->engine->getConnection()->insert('#__bpmn_job', [
            'id' => $job->getId(),
            'execution_id' => $job->getExecutionId(),
            'handler_type' => $job->getHandlerType(),
            'handler_data' => new BinaryData(serialize($job->getHandlerData())),
            'created_at' => time(),
            'run_at' => $time
        ]);
        
        $this->engine->info('Created job <{job}> of type "{handler}" targeting execution <{execution}>', [
            'job' => (string) $job->getId(),
            'handler' => $handlerType,
            'execution' => (string) $executionId
        ]);
        
        return $this->scheduledJobs[] = $job;
    }

    /**
     * {@inheritdoc}
     */
    public function executeJob(Job $job)
    {
        $stmt = $this->engine->prepareQuery("
            UPDATE `#__bpmn_job`
            SET `lock_owner` = :owner,
                `locked_at` = :time
            WHERE `id` = :id
            AND (`lock_owner` IS NULL OR `locked_at` < :expires)
        ");
        $stmt->bindValue('owner', $this->lockOwner);
        $stmt->bindValue('time', time());
        $stmt->bindValue('expires', time() - $this->lockTimeout);
        $stmt->bindValue('id', $job->getId());
        
        $locked = $stmt->execute();
        
        if ($locked < 1) {
            $jobs = $this->engine->getManagementService()->createJobQuery()->jobId($job->getId())->findAll();
            
            if (empty($jobs)) {
                $this->engine->info('Unable to lock job <{job}> because it does not exist in the DB', [
                    'job' => (string) $job->getId()
                ]);
                
                return;
            }
            
            $this->engine->info('Unable to lock job <{job}> because it is locked by job executor "{executor}"', [
                'job' => (string) $job->getId(),
                'executor' => $job->getLockOwner()
            ]);
            
            return;
        }
        
        $this->engine->executeCommand(new ExecuteJobCommand($job, $this->findJobHandler($job)));
        
        $jobs = $this->engine->getManagementService()->createJobQuery()->jobId($job->getId())->findAll();
        
        if (!empty($jobs)) {
            $job = array_pop($jobs);
            
            if ($job->getRetries() > 0) {
                $this->engine->info('Re-scheduling job <{job}>, {retries} retries left', [
                    'job' => (string) $job->getId(),
                    'retries' => $job->getRetries()
                ]);
                
                $this->scheduledJobs[] = $job;
            }
        }
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
            'job' => (string) $jobId
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
        foreach ($this->handlers as $type => $handler) {
            if ($job->getHandlerType() == $type) {
                return $handler;
            }
        }
        
        throw new \OutOfBoundsException(sprintf('Job handler "%s" not found for job %s', $job->getHandlerType(), $job->getId()));
    }
}
