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

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Job\Job;

/**
 * Base class for job scheduler implementations.
 * 
 * @author Martin Schröder
 */
abstract class AbstractJobScheduler implements JobSchedulerInterface
{
	/**
	 * @var ProcessEngine
	 */
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	/**
	 * Marks the job is being scheduled by writing a timestamp to the DB.
	 * 
	 * @param Job $job
	 */
	protected function markJobAsScheduled(Job $job)
	{
		$stmt = $this->engine->prepareQuery("UPDATE `#__bpmn_job` SET `scheduled_at` = :scheduled WHERE `id` = :id");
		$stmt->bindValue('scheduled', time());
		$stmt->bindValue('id', $job->getId());
		$stmt->execute();
	}
}
