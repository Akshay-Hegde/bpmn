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

class ImmediateJobExecutor extends AbstractJobExecutor
{
	public function scheduleJob(Job $job)
	{
		$stmt = $this->engine->prepareQuery("UPDATE `#__bpmn_job SET `scheduled` = :scheduled WHERE `id` = :id");
		$stmt->bindValue('scheduled', microtime(true));
		$stmt->bindValue('id', $job->getId());
		$stmt->execute();
		
		$this->executeJob($job->getId());
	}
}
