<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Job\JobQuery;
use KoolKode\Util\UUID;

class ManagementService
{
    protected $engine;

    public function __construct(ProcessEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * Create a new job query.
     * 
     * @return JobQuery
     */
    public function createJobQuery(): JobQuery
    {
        return new JobQuery($this->engine);
    }

    public function executeJob(UUID $jobId): void
    {
        $executor = $this->engine->getJobExecutor();
        
        if ($executor === null) {
            throw new \RuntimeException('Cannot remove job without a job executor');
        }
        
        $jobs = $this->createJobQuery()->jobId($jobId)->findAll();
        
        if (!empty($jobs)) {
            $executor->executeJob(array_pop($jobs));
        }
    }

    public function removeJob(UUID $jobId): void
    {
        $this->engine->getJobExecutor()->removeJob($jobId);
    }

    public function setJobRetries(UUID $jobId, int $retries): void
    {
        if ($retries < 0) {
            throw new \InvalidArgumentException(sprintf('Job retry count must not be negative'));
        }
        
        $stmt = $this->engine->prepareQuery("UPDATE `#__bpmn_job` SET `retries` = :retries WHERE `id` = :id");
        $stmt->bindValue('retries', $retries);
        $stmt->bindValue('id', $jobId);
        $stmt->execute();
    }
}
