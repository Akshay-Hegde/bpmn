<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\History;

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\StatementInterface;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class HistoricActivityInstanceQuery extends AbstractQuery
{
    protected $engine;

    protected $activityId;

    protected $processInstanceId;

    protected $processDefinitionId;

    protected $processDefinitionKey;

    protected $activityDefinitionKey;

    protected $completed;

    protected $canceled;

    public function __construct(ProcessEngine $engine)
    {
        $this->engine = $engine;
    }

    public function activityId($id): self
    {
        $this->populateMultiProperty($this->activityId, $id, function ($value) {
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

    public function processDefinitionId($id): self
    {
        $this->populateMultiProperty($this->processDefinitionId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function processDefinitionKey(string $key): self
    {
        $this->populateMultiProperty($this->processDefinitionKey, $key);
        
        return $this;
    }

    public function activityDefinitionKey(string $definitionKey): self
    {
        $this->populateMultiProperty($this->activityDefinitionKey, $definitionKey);
        
        return $this;
    }

    public function completed(bool $completed): self
    {
        $this->completed = $completed;
        
        return $this;
    }

    public function canceled($canceled): self
    {
        $this->canceled = $canceled;
        
        return $this;
    }

    public function orderByActivityDefinitionKey(bool $ascending = true): self
    {
        $this->orderings[] = [
            'a.`activity`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByStartedAt(bool $ascending = true): self
    {
        $this->orderings[] = [
            'a.`started_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByEndedAt(bool $ascending = true): self
    {
        $this->orderings[] = [
            'a.`ended_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByDuration(bool $ascending = true): self
    {
        $this->orderings[] = [
            'a.`duration`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function count(): int
    {
        $stmt = $this->executeSql(true);
        
        return (int) $stmt->fetchNextColumn(0);
    }

    public function findOne(): HistoricActivityInstance
    {
        $stmt = $this->executeSql(false, 1);
        $row = $stmt->fetchNextRow();
        
        if ($row === false) {
            throw new \OutOfBoundsException(\sprintf('No matching historic activity instance found'));
        }
        
        return $this->unserializeActivity($row);
    }

    public function findAll(): array
    {
        $stmt = $this->executeSql(false, $this->limit, $this->offset);
        $result = [];
        
        while ($row = $stmt->fetchNextRow()) {
            $result[] = $this->unserializeActivity($row);
        }
        
        return $result;
    }

    protected function unserializeActivity(array $row): HistoricActivityInstance
    {
        $activity = new HistoricActivityInstance($row['id'], $row['process_id'], $row['definition_id'], $row['process_key'], $row['activity'], $row['name'], $row['started_at']);
        
        $activity->setEndedAt($row['ended_at']);
        
        if ($row['duration'] !== null) {
            $activity->setDuration((float) $row['duration'] / 1000 + .001);
        }
        
        $activity->setCompleted($row['completed']);
        
        return $activity;
    }

    protected function getDefaultOrderBy(): array
    {
        return [
            'a.`id`',
            'ASC'
        ];
    }

    protected function executeSql(bool $count = false, int $limit = 0, int $offset = 0): StatementInterface
    {
        $fields = [];
        
        if ($count) {
            $fields[] = 'COUNT(*) AS num';
        } else {
            $fields[] = 'a.*';
            $fields[] = 'p.`definition_id`';
            $fields[] = 'd.`process_key`';
        }
        
        $sql = 'SELECT ' . \implode(', ', $fields) . ' FROM `#__bpmn_history_activity` AS a';
        $sql .= ' INNER JOIN `#__bpmn_history_process` AS p ON (p.`id` = a.`process_id`)';
        $sql .= ' INNER JOIN `#__bpmn_process_definition` AS d ON (p.`definition_id` = d.`id`)';
        
        $where = [];
        $params = [];
        
        $this->buildPredicate("a.`id`", $this->activityId, $where, $params);
        $this->buildPredicate("p.`id`", $this->processInstanceId, $where, $params);
        $this->buildPredicate('d.`id`', $this->processDefinitionId, $where, $params);
        $this->buildPredicate('d.`process_key`', $this->processDefinitionKey, $where, $params);
        $this->buildPredicate("a.`activity`", $this->activityDefinitionKey, $where, $params);
        
        if ($this->completed === true) {
            $where[] = 'a.`completed` = 1';
        } elseif ($this->completed === false) {
            $where[] = 'a.`completed` = 0';
        }
        
        if ($this->canceled === true) {
            $where[] = '(a.`duration` IS NOT NULL AND a.`completed` = 0)';
        } elseif ($this->canceled === false) {
            $where[] = '(a.`duration` IS NULL OR a.`completed` = 1)';
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
        $stmt->transform('process_id', new UUIDTransformer());
        $stmt->transform('definition_id', new UUIDTransformer());
        $stmt->transform('task_id', new UUIDTransformer());
        $stmt->transform('started_at', new DateTimeMillisTransformer());
        $stmt->transform('ended_at', new DateTimeMillisTransformer());
        $stmt->execute();
        
        return $stmt;
    }
}
