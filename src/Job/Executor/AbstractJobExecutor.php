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
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

abstract class AbstractJobExecutor implements JobExecutorInterface
{
	/**
	 * @var ProcessEngine
	 */
	protected $engine;
	
	protected $handlers = [];
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function registerJobHandler(JobHandlerInterface $handler)
	{
		$this->handlers[$handler->getType()] = $handler;
	}
	
	public function executeJob(UUID $jobId)
	{
		$stmt = $this->engine->prepareQuery("SELECT * FROM `#__bpmn_job` WHERE `id` = ?");
		$stmt->bindValue('id', $jobId);
		$stmt->transform('id', new UUIDTransformer());
		$stmt->transform('executionId', new UUIDTransformer());
		$stmt->execute();
		$row = $stmt->fetchNextRow();
		
		if($row === false)
		{
			throw new \OutOfBoundsException(sprintf('Job %s not found', $jobId));
		}
		
		$job = new Job(
			$row['id'],
			$row['execution_id'],
			$row['handler_type'],
			unserialize(BinaryData::decode($row['handler_data'])),
			$row['retries'],
			$row['lock_owner']
		);
		
		$this->engine->executeCommand(new ExecuteJobCommand($job, $this->findJobHandler($job)));
	}
	
	protected function findJobHandler(Job $job)
	{
		foreach($this->handlers as $type => $handler)
		{
			if($job->getHandlerType() == $type)
			{
				return $handler;
			}
		}
		
		throw new \OutOfBoundsException(sprintf('Job handler "%s" found for job %s', $job->getHandlerType(), $job->getId()));
	}
}
