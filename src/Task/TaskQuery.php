<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Task;

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\StatementInterface;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

/**
 * Query for active user tasks.
 * 
 * @author Martin Schröder
 */
class TaskQuery extends AbstractQuery
{
    protected $executionId;

    protected $processInstanceId;

    protected $processDefinitionKey;

    protected $processBusinessKey;

    protected $taskDefinitionKey;

    protected $taskId;

    protected $taskName;

    protected $taskUnassigned;

    protected $taskAssignee;

    protected $taskWithoutActivity;

    protected $dueBefore;

    protected $dueAfter;

    protected $taskCreatedBefore;

    protected $taskCreatedAfter;

    protected $taskPriority;

    protected $taskMinPriority;

    protected $taskMaxPriority;

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

    public function taskDefinitionKey(string $key): self
    {
        $this->populateMultiProperty($this->taskDefinitionKey, $key);
        
        return $this;
    }

    public function taskId($id): self
    {
        $this->populateMultiProperty($this->taskId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function taskName(string $name): self
    {
        $this->populateMultiProperty($this->taskName, $name);
        
        return $this;
    }

    public function taskUnassigned(): self
    {
        $this->taskUnassigned = true;
        
        return $this;
    }

    public function taskAssignee(string $assignee): self
    {
        $this->populateMultiProperty($this->taskAssignee, $assignee);
        
        return $this;
    }

    public function taskWithoutActivity(): self
    {
        $this->taskWithoutActivity = true;
        
        return $this;
    }

    public function dueBefore(\DateTimeImmutable $date): self
    {
        $this->dueBefore = $date->getTimestamp();
        
        return $this;
    }

    public function dueAfter(\DateTimeImmutable $date): self
    {
        $this->dueAfter = $date->getTimestamp();
        
        return $this;
    }

    public function taskPriority(int $priority): self
    {
        $this->populateMultiProperty($this->taskPriority, $priority);
        
        return $this;
    }

    public function taskMinPriority(int $priority): self
    {
        $this->taskMinPriority = $priority;
        
        return $this;
    }

    public function taskMaxPriority(int $priority): self
    {
        $this->taskMaxPriority = $priority;
        
        return $this;
    }

    public function taskCreatedBefore(\DateTimeImmutable $date): self
    {
        $this->taskCreatedBefore = $date->getTimestamp();
        
        return $this;
    }

    public function taskCreatedAfter(\DateTimeImmutable $date): self
    {
        $this->taskCreatedAfter = $date->getTimestamp();
        
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

    public function orderByProcessDefinitionKey(bool $ascending = true): self
    {
        $this->orderings[] = [
            'd.`process_key`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
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

    public function orderByTaskAssignee(bool $ascending = true): self
    {
        $this->orderings[] = [
            't.`claimed_by`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByTaskDefinitionKey(bool $ascending = true): self
    {
        $this->orderings[] = [
            't.`activity`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByTaskName(bool $ascending = true): self
    {
        $this->orderings[] = [
            't.`name`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByTaskPriority(bool $ascending = true): self
    {
        $this->orderings[] = [
            't.`priority`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByTaskCreated(bool $ascending = true): self
    {
        $this->orderings[] = [
            't.`created_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByTaskDue(bool $ascending = true): self
    {
        $this->orderings[] = [
            't.`due_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByTaskClaimed(bool $ascending = true): self
    {
        $this->orderings[] = [
            't.`claimed_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function count(): int
    {
        $stmt = $this->executeSql(true);
        
        return (int) $stmt->fetchNextColumn(0);
    }

    public function findOne(): TaskInterface
    {
        $stmt = $this->executeSql(false, 1);
        $row = $stmt->fetchNextRow();
        
        if ($row === false) {
            throw new \OutOfBoundsException(\sprintf('No matching task found'));
        }
        
        return $this->unserializeTask($row);
    }

    public function findAll(): array
    {
        $stmt = $this->executeSql(false, $this->limit, $this->offset);
        $result = [];
        
        while ($row = $stmt->fetchNextRow()) {
            $result[] = $this->unserializeTask($row);
        }
        
        return $result;
    }

    protected function unserializeTask(array $row): TaskInterface
    {
        $task = new Task($row['id'], $row['name'], new \DateTimeImmutable('@' . $row['created_at']), empty($row['claimed_at']) ? null : new \DateTimeImmutable('@' . $row['claimed_at']), $row['claimed_by'], $row['priority'], empty($row['due_at']) ? null : new \DateTimeImmutable('@' . $row['due_at']));
        
        $task->setDefinitionKey($row['activity']);
        $task->setDocumentation($row['documentation']);
        $task->setExecutionId($row['execution_id']);
        $task->setProcessInstanceId($row['process_id']);
        $task->setProcessBusinessKey($row['business_key']);
        
        return $task;
    }

    protected function getDefaultOrderBy(): array
    {
        return [
            't.`id`',
            'ASC'
        ];
    }

    protected function executeSql(bool $count = false, int $limit = 0, int $offset = 0): StatementInterface
    {
        if ($count) {
            $fields = 'COUNT(*) AS num';
        } else {
            $fields = 't.*, e.`process_id`, e.`business_key`';
        }
        
        $sql = "
            SELECT $fields
            FROM `#__bpmn_user_task` AS t
            LEFT JOIN `#__bpmn_execution` AS e ON (e.`id` = t.`execution_id`)
            LEFT JOIN `#__bpmn_process_definition` AS d ON (d.`id` = e.`definition_id`)
        ";
        
        $joins = [];
        $where = [];
        $params = [];
        
        $this->buildPredicate("e.`id`", $this->executionId, $where, $params);
        $this->buildPredicate("e.`process_id`", $this->processInstanceId, $where, $params);
        $this->buildPredicate("e.`business_key`", $this->processBusinessKey, $where, $params);
        $this->buildPredicate("d.`process_key`", $this->processDefinitionKey, $where, $params);
        
        $this->buildPredicate("t.`id`", $this->taskId, $where, $params);
        $this->buildPredicate("t.`activity`", $this->taskDefinitionKey, $where, $params);
        $this->buildPredicate("t.`name`", $this->taskName, $where, $params);
        
        $this->buildPredicate("t.`claimed_by`", $this->taskAssignee, $where, $params);
        
        if ($this->taskUnassigned) {
            $where[] = 't.`claimed_by` IS NULL';
        }
        
        if ($this->taskWithoutActivity) {
            $where[] = 't.`activity` IS NULL';
        }
        
        if ($this->dueAfter !== null || $this->dueBefore !== null) {
            $where[] = "t.`due_at` IS NOT NULL";
        }
        
        if ($this->dueBefore !== null) {
            $p1 = 'p' . \count($params);
            
            $where[] = "t.`due_at` < :$p1";
            $params[$p1] = $this->dueBefore;
        }
        
        if ($this->dueAfter !== null) {
            $p1 = 'p' . \count($params);
            
            $where[] = "t.`due_at` > :$p1";
            $params[$p1] = $this->dueAfter;
        }
        
        if ($this->taskCreatedBefore !== null) {
            $p1 = 'p' . \count($params);
            
            $where[] = "t.`created_at` < :$p1";
            $params[$p1] = $this->taskCreatedBefore;
        }
        
        if ($this->taskCreatedAfter !== null) {
            $p1 = 'p' . \count($params);
            
            $where[] = "t.`created_at` > :$p1";
            $params[$p1] = $this->taskCreatedAfter;
        }
        
        $this->buildPredicate("t.`priority`", $this->taskPriority, $where, $params);
        
        if ($this->taskMinPriority !== null) {
            $p1 = 'p' . \count($params);
            
            $where[] = "t.`priority` >= :$p1";
            $params[$p1] = $this->taskMinPriority;
        }
        
        if ($this->taskMaxPriority !== null) {
            $p1 = 'p' . \count($params);
            
            $where[] = "t.`priority` <= :$p1";
            $params[$p1] = $this->taskMaxPriority;
        }
        
        foreach ($joins as $join) {
            $sql .= ' ' . $join;
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . \implode(' AND ', $where);
        }
        
        if (!$count) {
            $sql .= $this->buildOrderings();
        }
        
        $stmt = $this->engine->prepareQuery($sql);
        $stmt->bindAll($params);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->transform('execution_id', new UUIDTransformer());
        $stmt->transform('process_id', new UUIDTransformer());
        $stmt->setLimit($limit);
        $stmt->setOffset($offset);
        $stmt->execute();
        
        return $stmt;
    }
}
