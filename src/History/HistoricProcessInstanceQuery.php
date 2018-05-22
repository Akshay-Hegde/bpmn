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
use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\StatementInterface;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class HistoricProcessInstanceQuery extends AbstractQuery
{
    protected $engine;

    protected $processInstanceId;

    protected $processDefinitionId;

    protected $processDefinitionKey;

    protected $processBusinessKey;

    protected $startActivityId;

    protected $endActivityId;

    protected $finished;

    public function __construct(ProcessEngine $engine)
    {
        $this->engine = $engine;
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

    public function processBusinessKey(string $key): self
    {
        $this->populateMultiProperty($this->processBusinessKey, $key);
        
        return $this;
    }

    public function startActivityId(string $id): self
    {
        $this->populateMultiProperty($this->startActivityId, $id);
        
        return $this;
    }

    public function endActivityId(string $id): self
    {
        $this->populateMultiProperty($this->endActivityId, $id);
        
        return $this;
    }

    public function finished(bool $finished): self
    {
        $this->finished = $finished;
        
        return $this;
    }

    public function orderByProcessBusinessKey(bool $ascending = true): self
    {
        $this->orderings[] = [
            'p.`business_key`',
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

    public function orderByStartActivityId(bool $ascending = true): self
    {
        $this->orderings[] = [
            'p.`start_activity`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByStarted(bool $ascending = true): self
    {
        $this->orderings[] = [
            'p.`started_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByEndActivityId(bool $ascending = true): self
    {
        $this->orderings[] = [
            'p.`end_activity`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByEnded(bool $ascending = true): self
    {
        $this->orderings[] = [
            'p.`ended_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByDuration(bool $ascending = true): self
    {
        $this->orderings[] = [
            'p.`duration`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function count(): int
    {
        $stmt = $this->executeSql(true);
        
        return (int) $stmt->fetchNextColumn(0);
    }

    public function findOne(): HistoricProcessInstance
    {
        $stmt = $this->executeSql(false, 1);
        $row = $stmt->fetchNextRow();
        
        if ($row === false) {
            throw new \OutOfBoundsException(sprintf('No matching historic process instance found'));
        }
        
        return $this->unserializeProcess($row);
    }

    public function findAll(): array
    {
        $stmt = $this->executeSql(false, $this->limit, $this->offset);
        $result = [];
        
        while ($row = $stmt->fetchNextRow()) {
            $result[] = $this->unserializeProcess($row);
        }
        
        return $result;
    }

    protected function unserializeProcess(array $row): HistoricProcessInstance
    {
        $process = new HistoricProcessInstance($row['id'], $row['definition_id'], $row['process_key'], $row['start_activity'], $row['started_at'], unserialize(BinaryData::decode($row['vars'])));
        
        $process->setBusinessKey($row['business_key']);
        $process->setEndActivityId($row['end_activity']);
        $process->setEndedAt($row['ended_at']);
        
        if ($row['duration'] !== null) {
            $process->setDuration((float) $row['duration'] / 1000 + .001);
        }
        
        return $process;
    }

    protected function getDefaultOrderBy(): array
    {
        return [
            'p.`id`',
            'ASC'
        ];
    }

    protected function executeSql(bool $count = false, int $limit = 0, int $offset = 0): StatementInterface
    {
        $fields = [];
        
        if ($count) {
            $fields[] = 'COUNT(*) AS num';
        } else {
            $fields[] = 'p.*';
            $fields[] = 'v.`data` AS vars';
            $fields[] = 'd.`process_key`';
        }
        
        $sql = 'SELECT ' . implode(', ', $fields) . ' FROM `#__bpmn_history_process` AS p';
        $sql .= ' INNER JOIN `#__bpmn_history_variables` AS v ON (v.`process_id` = p.`id`)';
        $sql .= ' INNER JOIN `#__bpmn_process_definition` AS d ON (p.`definition_id` = d.`id`)';
        
        $where = [];
        $params = [];
        
        $this->buildPredicate("p.`id`", $this->processInstanceId, $where, $params);
        $this->buildPredicate('p.`business_key`', $this->processBusinessKey, $where, $params);
        $this->buildPredicate("p.`start_activity`", $this->startActivityId, $where, $params);
        $this->buildPredicate("p.`end_activity`", $this->endActivityId, $where, $params);
        $this->buildPredicate('d.`id`', $this->processDefinitionId, $where, $params);
        $this->buildPredicate('d.`process_key`', $this->processDefinitionKey, $where, $params);
        
        if ($this->finished === true) {
            $where[] = 'p.`ended_at` IS NOT NULL';
        } elseif ($this->finished === false) {
            $where[] = 'p.`ended_at` IS NULL';
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        if (!$count) {
            $sql .= $this->buildOrderings();
        }
        
        $stmt = $this->engine->prepareQuery($sql);
        $stmt->bindAll($params);
        $stmt->setLimit($limit);
        $stmt->setOffset($offset);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->transform('definition_id', new UUIDTransformer());
        $stmt->transform('started_at', new DateTimeMillisTransformer());
        $stmt->transform('ended_at', new DateTimeMillisTransformer());
        $stmt->execute();
        
        return $stmt;
    }
}
