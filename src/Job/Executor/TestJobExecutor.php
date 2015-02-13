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

use KoolKode\BPMN\Job\Job;

class TestJobExecutor extends AbstractJobExecutor
{
	protected $jobs = [];
	
	public function scheduleJob(Job $job)
	{
		$stmt = $this->engine->prepareQuery("UPDATE `#__bpmn_job` SET `scheduled_at` = :scheduled WHERE `id` = :id");
		$stmt->bindValue('scheduled', time());
		$stmt->bindValue('id', $job->getId());
		$stmt->execute();
		
		$this->jobs[] = $job;
	}
	
	public function getPendingJobs()
	{
		return $this->jobs;
	}
	
	public function executeNextJob()
	{
		if(empty($this->jobs))
		{
			return false;
		}
		
		$job = array_shift($this->jobs);
		
		$this->executeJob($job->getId());
		
		return true;
	}
}
