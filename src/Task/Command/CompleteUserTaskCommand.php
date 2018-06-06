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

namespace KoolKode\BPMN\Task\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Task\Event\UserTaskCompletedEvent;
use KoolKode\Util\UUID;

/**
 * Completes a user task and signals process execution to continue.
 * 
 * @author Martin Schröder
 */
class CompleteUserTaskCommand extends AbstractBusinessCommand
{
    protected $taskId;

    protected $variables;

    public function __construct(UUID $taskId, array $variables = [])
    {
        $this->taskId = $taskId;
        $this->variables = \serialize($variables);
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

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ProcessEngine $engine): void
    {
        $task = $engine->getTaskService()->createTaskQuery()->taskId($this->taskId)->findOne();
        
        $engine->notify(new UserTaskCompletedEvent($task, $engine));
        
        $stmt = $engine->prepareQuery("DELETE FROM `#__bpmn_user_task` WHERE `id` = :id");
        $stmt->bindValue('id', $this->taskId);
        $stmt->execute();
        
        $engine->debug('Completed user task "{task}" with id {id}', [
            'task' => $task->getName(),
            'id' => (string) $task->getId()
        ]);
        
        $executionId = $task->getExecutionId();
        
        if ($executionId !== null) {
            $engine->findExecution($executionId)->signal('user-task', \unserialize($this->variables));
        }
    }
}