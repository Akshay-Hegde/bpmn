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

use KoolKode\BPMN\Job\Job;
use KoolKode\Util\UUID;

/**
 * The test job scheduler will simply mark jobs as scheduled in the DB.
 * 
 * @author Martin Schröder
 */
class TestJobScheduler extends AbstractJobScheduler
{
    /**
     * {@inheritdoc}
     */
    public function scheduleJob(Job $job): void
    {
        $this->markJobAsScheduled($job);
    }

    /**
     * {@inheritdoc}
     */
    public function removeJob(UUID $jobId): void
    {
        // Nothing to do here...
    }
}
