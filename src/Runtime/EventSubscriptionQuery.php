<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Runtime;

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class EventSubscriptionQuery extends AbstractQuery
{
    protected $activityId;

    protected $eventName;

    protected $eventSubscriptionId;

    protected $eventType;

    protected $executionId;

    protected $processInstanceId;

    protected $jobId;

    protected $boundaryEvent;

    public function __construct(ProcessEngine $engine)
    {
        $this->engine = $engine;
    }

    public function activityId($id)
    {
        $this->populateMultiProperty($this->activityId, $id);
        
        return $this;
    }

    public function eventName($name)
    {
        $this->populateMultiProperty($this->eventName, $name);
        
        return $this;
    }

    public function eventSubscriptionId($id)
    {
        $this->populateMultiProperty($this->eventSubscriptionId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function eventType($type)
    {
        $this->populateMultiProperty($this->eventType, $type, function ($value) {
            return (int) $value;
        });
        
        return $this;
    }

    public function executionId($id)
    {
        $this->populateMultiProperty($this->executionId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function processInstanceId($id)
    {
        $this->populateMultiProperty($this->processInstanceId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function jobId($id)
    {
        $this->populateMultiProperty($this->jobId, $id, function ($value) {
            return new UUID($value);
        });
        
        return $this;
    }

    public function boundaryEvent($boundaryEvent)
    {
        $this->boundaryEvent = $boundaryEvent ? true : false;
        
        return $this;
    }

    public function orderByActivityId($ascending = true)
    {
        $this->orderings[] = [
            's.`activity_id`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByProcessInstanceId($ascending = true)
    {
        $this->orderings[] = [
            's.`process_instance_id`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByCreated($ascending = true)
    {
        $this->orderings[] = [
            's.`created_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function count()
    {
        $stmt = $this->executeSql(true);
        
        return (int) $stmt->fetchNextColumn(0);
    }

    public function findOne()
    {
        $stmt = $this->executeSql(false, 1);
        $row = $stmt->fetchNextRow();
        
        if ($row === false) {
            throw new \OutOfBoundsException(sprintf('No matching event subscription found'));
        }
        
        return $this->unserializeSubscription($row);
    }

    public function findAll()
    {
        $stmt = $this->executeSql(false, $this->limit, $this->offset);
        $result = [];
        
        while ($row = $stmt->fetchNextRow()) {
            $result[] = $this->unserializeSubscription($row);
        }
        
        return $result;
    }

    protected function unserializeSubscription(array $row)
    {
        $subscription = new EventSubscription($row['id'], $row['execution_id'], $row['process_instance_id'], $row['activity_id'], $row['flags'], $row['name'], new \DateTimeImmutable('@' . $row['created_at']));
        
        $subscription->setJobId($row['job_id']);
        $subscription->setBoundaryEvent($row['boundary']);
        
        return $subscription;
    }

    protected function getDefaultOrderBy(): array
    {
        return [
            's.`id`',
            'ASC'
        ];
    }

    protected function executeSql($count = false, $limit = 0, $offset = 0)
    {
        if ($count) {
            $fields = 'COUNT(*) AS num';
        } else {
            $fields = 's.*';
        }
        
        $sql = "
            SELECT $fields
            FROM `#__bpmn_event_subscription` AS s
            INNER JOIN `#__bpmn_execution` AS e ON (e.`id` = s.`execution_id`)
        ";
        
        $where = [];
        $params = [];
        
        $this->buildPredicate("s.`id`", $this->eventSubscriptionId, $where, $params);
        $this->buildPredicate("s.`activity_id`", $this->activityId, $where, $params);
        $this->buildPredicate("s.`name`", $this->eventName, $where, $params);
        $this->buildPredicate("s.`flags`", $this->eventType, $where, $params);
        $this->buildPredicate("s.`job_id`", $this->jobId, $where, $params);
        $this->buildPredicate("e.`id`", $this->executionId, $where, $params);
        $this->buildPredicate('e.`process_id`', $this->processInstanceId, $where, $params);
        
        if ($this->boundaryEvent === true) {
            $where[] = 's.`boundary` = 1';
        } elseif ($this->boundaryEvent === false) {
            $where[] = 's.`boundary` = 0';
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        
        if (!$count) {
            $sql .= $this->buildOrderings();
        }
        
        $stmt = $this->engine->prepareQuery($sql);
        $stmt->bindAll($params);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->transform('execution_id', new UUIDTransformer());
        $stmt->transform('process_instance_id', new UUIDTransformer());
        $stmt->transform('job_id', new UUIDTransformer());
        $stmt->setLimit($limit);
        $stmt->setOffset($offset);
        $stmt->execute();
        
        return $stmt;
    }
}
