<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace KoolKode\BPMN\Job;

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\StatementInterface;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class JobQuery extends AbstractQuery
{
    protected $executionId;

    protected $externalId;

    protected $processInstanceId;

    protected $processDefinitionKey;

    protected $processBusinessKey;

    protected $jobId;

    protected $jobRetries;

    protected $jobLockOwner;

    protected $jobHandlerType;

    protected $isScheduled;

    protected $isTimer;

    protected $isFailed;

    protected $engine;

    public function __construct(ProcessEngine $engine)
    {
        $this->engine = $engine;
    }

    public function executionId($id): self
    {
        $this->populateMultiProperty($this->executionId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function externalId(string $id): self
    {
        $this->populateMultiProperty($this->externalId, $id);
        
        return $this;
    }

    public function processInstanceId($id): self
    {
        $this->populateMultiProperty($this->processInstanceId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function processDefinitionKey(string $key): self
    {
        $this->populateMultiProperty($this->processDefinitionKey, $key);
        
        return $this;
    }

    public function processBusinessKey(string $key): self
    {
        $this->populateMultiProperty($this->processBusinessKey, $key);
        
        return $this;
    }

    public function jobId($id): self
    {
        $this->populateMultiProperty($this->jobId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function jobRetries(int $retries): self
    {
        $this->populateMultiProperty($this->jobRetries, $retries);
        
        return $this;
    }

    public function jobLockOwner(string $owner): self
    {
        $this->populateMultiProperty($this->jobLockOwner, $owner);
        
        return $this;
    }

    public function jobHandlerType(string $handlerType): self
    {
        $this->populateMultiProperty($this->jobHandlerType, $handlerType);
        
        return $this;
    }

    public function scheduled(?bool $scheduled = true): self
    {
        $this->isScheduled = $scheduled;
        
        return $this;
    }

    public function timer(?bool $timer = true): self
    {
        $this->isTimer = $timer;
        
        return $this;
    }

    public function failed(?bool $failed = true): self
    {
        $this->isFailed = $failed;
        
        return $this;
    }

    public function orderByProcessInstanceId(bool $ascending = true): self
    {
        $this->orderings[] = [
            'e.`process_id`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByProcessBusinessKey(bool $ascending = true): self
    {
        $this->orderings[] = [
            'e.`business_key`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByProcessDefinitionId(bool $ascending = true): self
    {
        $this->orderings[] = [
            'd.`id`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByProcessDefinitionKey(bool $ascending = true): self
    {
        $this->orderings[] = [
            'd.`process_key`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByExternalId(bool $ascending = true): self
    {
        $this->orderings[] = [
            'j.`external_id`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByCreated(bool $ascending = true): self
    {
        $this->orderings[] = [
            'j.`created_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByScheduled(bool $ascending = true): self
    {
        $this->orderings[] = [
            'j.`scheduled_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByRun(bool $ascending = true): self
    {
        $this->orderings[] = [
            'j.`run_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByHandlerType(bool $ascending = true): self
    {
        $this->orderings[] = [
            'j.`handler_type`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByJobId(bool $ascending = true): self
    {
        $this->orderings[] = [
            'j.`id`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByRetries(bool $ascending = true): self
    {
        $this->orderings[] = [
            'j.`retries`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByLockOwner(bool $ascending = true): self
    {
        $this->orderings[] = [
            'j.`lock_owner`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function count(): int
    {
        $stmt = $this->executeSql(true);
        
        return (int) $stmt->fetchNextColumn(0);
    }

    public function findOne(): JobInterface
    {
        $stmt = $this->executeSql(false, 1);
        $row = $stmt->fetchNextRow();
        
        if ($row === false) {
            throw new \OutOfBoundsException(\sprintf('No matching job found'));
        }
        
        return $this->unserializeJob($row);
    }

    public function findAll(): array
    {
        $stmt = $this->executeSql(false, $this->limit, $this->offset);
        $result = [];
        
        while ($row = $stmt->fetchNextRow()) {
            $result[] = $this->unserializeJob($row);
        }
        
        return $result;
    }

    protected function unserializeJob(array $row): JobInterface
    {
        $job = new Job(...[
            $row['id'],
            $row['execution_id'],
            $row['handler_type'],
            \unserialize(BinaryData::decode($row['handler_data'])),
            new \DateTimeImmutable('@' . $row['created_at']),
            (int) $row['retries'],
            $row['lock_owner']
        ]);
        
        $job->setExternalId($row['external_id']);
        
        if ($row['scheduled_at'] !== null) {
            $job->setScheduledAt(new \DateTimeImmutable('@' . $row['scheduled_at'], new \DateTimeZone('UTC')));
        }
        
        if ($row['run_at'] !== null) {
            $job->setRunAt(new \DateTimeImmutable('@' . $row['run_at'], new \DateTimeZone('UTC')));
        }
        
        if ($row['locked_at'] !== null) {
            $job->setLockedAt(new \DateTimeImmutable('@' . $row['locked_at'], new \DateTimeZone('UTC')));
        }
        
        $job->setExceptionType($row['exception_type']);
        $job->setExceptionMessage($row['exception_message']);
        
        if ($row['exception_data'] !== null) {
            $job->setExceptionData(\unserialize(BinaryData::decode($row['exception_data'])));
        }
        
        $locked = false;
        
        if ($row['lock_owner'] !== null && $row['locked_at'] !== null) {
            if ($row['locked_at'] > (\time() - $this->engine->getJobExecutor()->getLockTimeout())) {
                $locked = true;
            }
        }
        
        $job->setLocked($locked);
        
        return $job;
    }

    protected function getDefaultOrderBy(): array
    {
        return [
            'j.`id`',
            'ASC'
        ];
    }

    protected function executeSql(bool $count = false, int $limit = 0, int $offset = 0): StatementInterface
    {
        $fields = [];
        
        if ($count) {
            $fields[] = 'COUNT(*) AS num';
        } else {
            $fields[] = 'j.*';
        }
        
        $sql = 'SELECT ' . \implode(', ', $fields) . ' FROM `#__bpmn_job` AS j';
        $sql .= ' LEFT JOIN `#__bpmn_execution` AS e ON (e.`id` = j.`execution_id`)';
        $sql .= ' LEFT JOIN `#__bpmn_process_definition` AS d ON (d.`id` = e.`definition_id`)';
        
        $where = [];
        $params = [];
        
        $this->buildPredicate("e.`id`", $this->executionId, $where, $params);
        $this->buildPredicate("e.`external_id`", $this->externalId, $where, $params);
        $this->buildPredicate("e.`process_id`", $this->processInstanceId, $where, $params);
        $this->buildPredicate("e.`business_key`", $this->processBusinessKey, $where, $params);
        $this->buildPredicate("d.`process_key`", $this->processDefinitionKey, $where, $params);
        
        $this->buildPredicate("j.`id`", $this->jobId, $where, $params);
        $this->buildPredicate("j.`retries`", $this->jobRetries, $where, $params);
        $this->buildPredicate("j.`lock_owner`", $this->jobLockOwner, $where, $params);
        $this->buildPredicate("j.`handler_type`", $this->jobHandlerType, $where, $params);
        
        if ($this->isScheduled === true) {
            $where[] = 'j.`scheduled_at` IS NOT NULL';
        } elseif ($this->isScheduled === false) {
            $where[] = 'j.`scheduled_at` IS NULL';
        }
        
        if ($this->isTimer === true) {
            $where[] = 'j.`run_at` IS NOT NULL';
        } elseif ($this->isTimer === false) {
            $where[] = 'j.`run_at` IS NULL';
        }
        
        if ($this->isFailed === true) {
            $where[] = '(j.`exception_type` IS NOT NULL AND j.`retries` = 0)';
        } elseif ($this->isFailed === false) {
            $where[] = '(j.`exception_type` IS NULL OR j.`retries` <> 0)';
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . \implode(' AND ', $where);
        }
        
        if (!$count) {
            $sql .= $this->buildOrderings();
        }
        
        $stmt = $this->engine->prepareQuery($sql);
        $stmt->bindAll($params);
        $stmt->setLimit($limit);
        $stmt->setOffset($offset);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->transform('execution_id', new UUIDTransformer());
        $stmt->execute();
        
        return $stmt;
    }
}
