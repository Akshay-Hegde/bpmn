<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Task\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Task\TaskInterface;
use KoolKode\BPMN\Task\Event\UserTaskCreatedEvent;
use KoolKode\Util\UUID;

/**
 * Creates a new user task instance.
 * 
 * @author Martin Schröder
 */
class CreateUserTaskCommand extends AbstractBusinessCommand
{
    protected $name;

    protected $priority;

    protected $dueDate;

    protected $executionId;

    protected $documentation;

    public function __construct(string $name, int $priority, ?VirtualExecution $execution = null, ?string $documentation = null)
    {
        $this->name = $name;
        $this->priority = $priority;
        $this->executionId = ($execution === null) ? null : $execution->getId();
        $this->documentation = $documentation;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function isSerializable(): bool
    {
        return true;
    }

    public function setDueDate(?\DateTimeImmutable $dueDate)
    {
        if ($dueDate === null) {
            $this->dueDate = null;
        } else {
            $this->dueDate = $dueDate->getTimestamp();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ProcessEngine $engine): TaskInterface
    {
        $id = UUID::createRandom();
        $activityId = null;
        
        if ($this->executionId !== null) {
            $activityId = $engine->findExecution($this->executionId)->getNode()->getId();
        }
        
        $sql = "
            INSERT INTO `#__bpmn_user_task`
                (`id`, `execution_id`, `name`, `documentation`, `activity`, `created_at`, `priority`, `due_at`)
            VALUES
                (:id, :eid, :name, :doc, :activity, :created, :priority, :due)
        ";
        $stmt = $engine->prepareQuery($sql);
        $stmt->bindValue('id', $id);
        $stmt->bindValue('eid', $this->executionId);
        $stmt->bindValue('name', $this->name);
        $stmt->bindValue('doc', $this->documentation);
        $stmt->bindValue('activity', $activityId);
        $stmt->bindValue('created', time());
        $stmt->bindValue('priority', $this->priority);
        $stmt->bindValue('due', $this->dueDate);
        $stmt->execute();
        
        $engine->debug('Created user task "{task}" with id {id}', [
            'task' => $this->name,
            'id' => (string) $id
        ]);
        
        $task = $engine->getTaskService()->createTaskQuery()->taskId($id)->findOne();
        
        $engine->notify(new UserTaskCreatedEvent($task, $engine));
        
        return $task;
    }
}
