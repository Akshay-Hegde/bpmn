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

/**
 * The test job executor will simply mark jobs as scheduled in the DB.
 * 
 * @author Martin Schröder
 */
class TestJobExecutor extends AbstractJobExecutor
{
	/**
	 * {@inheritdoc}
	 */
	public function scheduleJob(Job $job)
	{
		$this->markJobAsScheduled($job);
	}
}
