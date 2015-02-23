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

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\History\Event\AbstractAuditEvent;
use KoolKode\BPMN\History\Event\ActivityCanceledEvent;
use KoolKode\BPMN\History\Event\ActivityCompletedEvent;
use KoolKode\BPMN\History\Event\ActivityStartedEvent;
use KoolKode\BPMN\History\Event\ExecutionCreatedEvent;
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
	
	public function createHistoricActivityInstanceQuery()
	{
		return new HistoricActivityInstanceQuery($this->engine);
	}
	
	public function recordEvent(AbstractAuditEvent $event)
	{
		if($event instanceof ExecutionCreatedEvent)
		{
			$this->recordExecutionCreated($event);
		}
		elseif($event instanceof ExecutionTerminatedEvent)
		{
			$this->recordExecutionTerminated($event);
		}
		elseif($event instanceof ActivityStartedEvent)
		{
			$this->recordActivityStarted($event);
		}
		elseif($event instanceof ActivityCompletedEvent)
		{
			$this->recordActivityCompleted($event);
		}
		elseif($event instanceof ActivityCanceledEvent)
		{
			$this->recordActivityCanceled($event);
		}
		elseif($event instanceof UserTaskCreatedEvent)
		{
			$this->recordUserTaskCreated($event);
		}
		elseif($event instanceof UserTaskClaimedEvent)
		{
			$this->recordUserTaskClaimed($event);
		}
		elseif($event instanceof UserTaskUnclaimedEvent)
		{
			$this->recordUserTaskUnclaimed($event);
		}
		elseif($event instanceof UserTaskCompletedEvent)
		{
			$this->recordUserTaskCompleted($event);
		}
	}
	
	protected function recordExecutionCreated(ExecutionCreatedEvent $event)
	{
		$this->engine->getConnection()->insert('#__bpmn_history_execution', [
			'id' => $event->execution->getId(),
			'process_id' => $event->execution->getRootExecution()->getId(),
			'definition_id' => new UUID($event->execution->getProcessModel()->getId()),
			'started_at' => DateTimeMillisTransformer::encode($event->timestamp)
		]);
	}
	
	protected function recordExecutionTerminated(ExecutionTerminatedEvent $event)
	{
		$stmt = $this->engine->prepareQuery("
			UPDATE `#__bpmn_history_execution`
			SET `ended_at` = :timestamp,
				`duration` = :timestamp - `started_at`
			WHERE `id` = :execution
		");
		$stmt->bindValue('timestamp', DateTimeMillisTransformer::encode($event->timestamp));
		$stmt->bindValue('execution', $event->execution->getId());
		$stmt->execute();
		
		$stmt = $this->engine->prepareQuery("
			UPDATE `#__bpmn_history_execution`
			SET `duration` = `ended_at` - `started_at`
			WHERE `id` = :execution
		");
		$stmt->bindValue('execution', $event->execution->getId());
		$stmt->execute();
	}
	
	protected function recordActivityStarted(ActivityStartedEvent $event)
	{
		$this->engine->getConnection()->insert('#__bpmn_history_activity', [
			'id' => UUID::createRandom(),
			'execution_id' => $event->execution->getId(),
			'activity' => $event->name,
			'started_at' => DateTimeMillisTransformer::encode($event->timestamp)
		]);
	}
	
	protected function recordActivityCompleted(ActivityCompletedEvent $event)
	{
		$stmt = $this->engine->prepareQuery("
			SELECT `id`
			FROM `#__bpmn_history_activity`
			WHERE `execution_id` = :execution AND `activity` = :activity
			ORDER BY `started_at` DESC
		");
		$stmt->setLimit(1);
		$stmt->bindValue('execution', $event->execution->getId());
		$stmt->bindValue('activity', $event->name);
		$stmt->transform('id', new UUIDTransformer());
		$stmt->execute();
		
		if(false !== ($id = $stmt->fetchNextColumn('id')))
		{
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
	
	protected function recordActivityCanceled(ActivityCanceledEvent $event)
	{
		$stmt = $this->engine->prepareQuery("
			SELECT `id`
			FROM `#__bpmn_history_activity`
			WHERE `execution_id` = :execution AND `activity` = :activity
			ORDER BY `started_at` DESC
		");
		$stmt->setLimit(1);
		$stmt->bindValue('execution', $event->execution->getId());
		$stmt->bindValue('activity', $event->name);
		$stmt->transform('id', new UUIDTransformer());
		$stmt->execute();
		
		if(false !== ($id = $stmt->fetchNextColumn('id')))
		{
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
	
	protected function recordUserTaskCreated(UserTaskCreatedEvent $event)
	{
		$executionId = $event->task->getExecutionId();
		
		$this->engine->getConnection()->insert('#__bpmn_history_task', [
			'id' => $event->task->getId(),
			'execution_id' => $executionId,
			'definition_key' => $event->task->getDefinitionKey(),
			'assignee' => $event->task->getAssignee(),
			'priority' => $event->task->getPriority(),
			'started_at' => DateTimeMillisTransformer::encode($event->timestamp)
		]);
	}
	
	protected function recordUserTaskClaimed(UserTaskClaimedEvent $event)
	{
		$this->engine->getConnection()->update('#__bpmn_history_task', [
			'id' => $event->task->getId()
		], [
			'assignee' => $event->task->getAssignee()
		]);
	}
	
	protected function recordUserTaskUnclaimed(UserTaskUnclaimedEvent $event)
	{
		$this->engine->getConnection()->update('#__bpmn_history_task', [
			'id' => $event->task->getId()
		], [
			'assignee' => NULL
		]);
	}
	
	protected function recordUserTaskCompleted(UserTaskCompletedEvent $event)
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
