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

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Job\Handler\JobHandlerInterface;
use KoolKode\BPMN\Job\Job;
use KoolKode\Util\UUID;

/**
 * Execute a job within an engine execution cycle
 * 
 * @author Martin Schröder
 */
class ExecuteJobCommand extends AbstractBusinessCommand
{
	protected $job;
	
	protected $handler;
	
	public function __construct(Job $job, JobHandlerInterface $handler)
	{
		$this->job = $job;
		$this->handler = $handler;
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function executeCommand(ProcessEngine $engine)
	{
		$execution = $engine->findExecution($this->job->getExecutionId());
		
		$engine->debug('Executing job <{job}> using handler "{handler}" ({impl}) within {execution}', [
			'job' => (string)$this->job->getId(),
			'handler' => $this->handler->getType(),
			'impl' => get_class($this->handler),
			'execution' => (string)$execution
		]);
		
		$this->handler->executeJob($this->job, $execution, $engine);
	}
}
