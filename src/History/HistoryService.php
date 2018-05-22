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

use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\History\Event\AbstractAuditEvent;
use KoolKode\BPMN\History\Event\ActivityCanceledEvent;
use KoolKode\BPMN\History\Event\ActivityCompletedEvent;
use KoolKode\BPMN\History\Event\ActivityStartedEvent;
use KoolKode\BPMN\History\Event\ExecutionCreatedEvent;
use KoolKode\BPMN\History\Event\ExecutionModifiedEvent;
use KoolKode\BPMN\History\Event\ExecutionTerminatedEvent;
use KoolKode\BPMN\Task\Event\UserTaskClaimedEvent;
use KoolKode\BPMN\Task\Event\UserTaskCompletedEvent;
use KoolKode\BPMN\Task\Event\UserTaskCreatedEvent;
use KoolKode\BPMN\Task\Event\UserTaskUnclaimedEvent;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class HistoryService
{
    protected $engine;

    public function __construct(ProcessEngine $engine)
    {
        $this->engine = $engine;
    }

    public function createHistoricProcessInstanceQuery(): HistoricProcessInstanceQuery
    {
        return new HistoricProcessInstanceQuery($this->engine);
    }

    public function createHistoricActivityInstanceQuery(): HistoricActivityInstanceQuery
    {
        return new HistoricActivityInstanceQuery($this->engine);
    }

    public function recordEvent(AbstractAuditEvent $event): void
    {
        if ($event instanceof ExecutionCreatedEvent) {
            $this->recordExecutionCreated($event);
        } elseif ($event instanceof ExecutionModifiedEvent) {
            $this->recordExecutionModified($event);
        } elseif ($event instanceof ExecutionTerminatedEvent) {
            $this->recordExecutionTerminated($event);
        } elseif ($event instanceof ActivityStartedEvent) {
            $this->recordActivityStarted($event);
        } elseif ($event instanceof ActivityCompletedEvent) {
            $this->recordActivityCompleted($event);
        } elseif ($event instanceof ActivityCanceledEvent) {
            $this->recordActivityCanceled($event);
        } elseif ($event instanceof UserTaskCreatedEvent) {
            $this->recordUserTaskCreated($event);
        } elseif ($event instanceof UserTaskClaimedEvent) {
            $this->recordUserTaskClaimed($event);
        } elseif ($event instanceof UserTaskUnclaimedEvent) {
            $this->recordUserTaskUnclaimed($event);
        } elseif ($event instanceof UserTaskCompletedEvent) {
            $this->recordUserTaskCompleted($event);
        }
    }

    protected function recordExecutionCreated(ExecutionCreatedEvent $event): void
    {
        if (!$event->execution->isRootExecution()) {
            return;
        }
        
        $this->engine->getConnection()->insert('#__bpmn_history_process', [
            'id' => $event->execution->getId(),
            'definition_id' => new UUID($event->execution->getProcessModel()->getId()),
            'business_key' => $event->execution->getBusinessKey(),
            'start_activity' => $event->execution->getNode()->getId(),
            'started_at' => DateTimeMillisTransformer::encode($event->timestamp)
        ]);
        
        $this->engine->getConnection()->insert('#__bpmn_history_variables', [
            'process_id' => $event->execution->getId(),
            'data' => new BinaryData(serialize($event->variables))
        ]);
    }

    protected function recordExecutionModified(ExecutionModifiedEvent $event): void
    {
        if (!$event->execution->isRootExecution()) {
            return;
        }
        
        $this->engine->getConnection()->update('#__bpmn_history_process', [
            'id' => $event->execution->getId()
        ], [
            'definition_id' => new UUID($event->execution->getProcessModel()->getId()),
            'business_key' => $event->execution->getBusinessKey()
        ]);
        
        $this->engine->getConnection()->update('#__bpmn_history_variables', [
            'process_id' => $event->execution->getId()
        ], [
            'data' => new BinaryData(serialize($event->variables))
        ]);
    }

    protected function recordExecutionTerminated(ExecutionTerminatedEvent $event): void
    {
        if (!$event->execution->isRootExecution()) {
            return;
        }
        
        $stmt = $this->engine->prepareQuery("
            UPDATE `#__bpmn_history_process`
            SET `ended_at` = :timestamp,
                `duration` = :timestamp - `started_at`,
                `end_activity` = :activity
            WHERE `id` = :execution
        ");
        $stmt->bindValue('timestamp', DateTimeMillisTransformer::encode($event->timestamp));
        $stmt->bindValue('activity', $event->execution->getNode()->getId());
        $stmt->bindValue('execution', $event->execution->getId());
        $stmt->execute();
        
        $stmt = $this->engine->prepareQuery("
            UPDATE `#__bpmn_history_process`
            SET `duration` = `ended_at` - `started_at`
            WHERE `id` = :execution
        ");
        $stmt->bindValue('execution', $event->execution->getId());
        $stmt->execute();
    }

    protected function recordActivityStarted(ActivityStartedEvent $event): void
    {
        $this->engine->getConnection()->insert('#__bpmn_history_activity', [
            'id' => UUID::createRandom(),
            'process_id' => $event->execution->getRootExecution()->getId(),
            'activity' => $event->name,
            'started_at' => DateTimeMillisTransformer::encode($event->timestamp)
        ]);
    }

    protected function recordActivityCompleted(ActivityCompletedEvent $event): void
    {
        $stmt = $this->engine->prepareQuery("
            SELECT `id`
            FROM `#__bpmn_history_activity`
            WHERE `process_id` = :execution AND `activity` = :activity
            ORDER BY `started_at` DESC
        ");
        $stmt->setLimit(1);
        $stmt->bindValue('execution', $event->execution->getRootExecution()->getId());
        $stmt->bindValue('activity', $event->name);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->execute();
        
        if (false !== ($id = $stmt->fetchNextColumn('id'))) {
            $stmt = $this->engine->prepareQuery("
                UPDATE `#__bpmn_history_activity`
                SET `ended_at` = :timestamp,
                    `completed` = 1
                WHERE `id` = :id
            ");
            $stmt->bindValue('timestamp', DateTimeMillisTransformer::encode($event->timestamp));
            $stmt->bindValue('id', $id);
            $stmt->execute();
            
            $stmt = $this->engine->prepareQuery("
                UPDATE `#__bpmn_history_activity`
                SET `duration` = `ended_at` - `started_at`
                WHERE `id` = :id
            ");
            $stmt->bindValue('id', $id);
            $stmt->execute();
        }
    }

    protected function recordActivityCanceled(ActivityCanceledEvent $event): void
    {
        $stmt = $this->engine->prepareQuery("
            SELECT `id`
            FROM `#__bpmn_history_activity`
            WHERE `process_id` = :execution AND `activity` = :activity
            ORDER BY `started_at` DESC
        ");
        $stmt->setLimit(1);
        $stmt->bindValue('execution', $event->execution->getRootExecution()->getId());
        $stmt->bindValue('activity', $event->name);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->execute();
        
        if (false !== ($id = $stmt->fetchNextColumn('id'))) {
            $stmt = $this->engine->prepareQuery("
                UPDATE `#__bpmn_history_activity`
                SET `ended_at` = :timestamp
                WHERE `id` = :id
            ");
            $stmt->bindValue('timestamp', DateTimeMillisTransformer::encode($event->timestamp));
            $stmt->bindValue('id', $id);
            $stmt->execute();
            
            $stmt = $this->engine->prepareQuery("
                UPDATE `#__bpmn_history_activity`
                SET `duration` = `ended_at` - `started_at`
                WHERE `id` = :id
            ");
            $stmt->bindValue('id', $id);
            $stmt->execute();
        }
    }

    protected function recordUserTaskCreated(UserTaskCreatedEvent $event): void
    {
        $this->engine->getConnection()->insert('#__bpmn_history_task', [
            'id' => $event->task->getId(),
            'process_id' => $this->engine->findExecution($event->task->getExecutionId())->getRootExecution()->getId(),
            'definition_key' => $event->task->getDefinitionKey(),
            'assignee' => $event->task->getAssignee(),
            'priority' => $event->task->getPriority(),
            'started_at' => DateTimeMillisTransformer::encode($event->timestamp)
        ]);
    }

    protected function recordUserTaskClaimed(UserTaskClaimedEvent $event): void
    {
        $this->engine->getConnection()->update('#__bpmn_history_task', [
            'id' => $event->task->getId()
        ], [
            'assignee' => $event->task->getAssignee()
        ]);
    }

    protected function recordUserTaskUnclaimed(UserTaskUnclaimedEvent $event): void
    {
        $this->engine->getConnection()->update('#__bpmn_history_task', [
            'id' => $event->task->getId()
        ], [
            'assignee' => null
        ]);
    }

    protected function recordUserTaskCompleted(UserTaskCompletedEvent $event): void
    {
        $stmt = $this->engine->prepareQuery("
            UPDATE `#__bpmn_history_task`
            SET `ended_at` = :timestamp,
                `completed` = 1
            WHERE `id` = :task
        ");
        $stmt->bindValue('timestamp', DateTimeMillisTransformer::encode($event->timestamp));
        $stmt->bindValue('task', $event->task->getId());
        $stmt->execute();
        
        $stmt = $this->engine->prepareQuery("
            UPDATE `#__bpmn_history_task`
            SET `duration` = `ended_at` - `started_at`
            WHERE `id` = :task
        ");
        $stmt->bindValue('task', $event->task->getId());
        $stmt->execute();
    }
}
