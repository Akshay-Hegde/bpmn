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
use KoolKode\BPMN\Task\Event\UserTaskClaimedEvent;
use KoolKode\Util\UUID;

/**
 * Claims a user task by setting the assignee.
 * 
 * @author Martin Schröder
 */
class ClaimUserTaskCommand extends AbstractBusinessCommand
{
    protected $taskId;

    protected $assignee;

    public function __construct(UUID $taskId, string $assignee)
    {
        $this->taskId = $taskId;
        $this->assignee = $assignee;
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
        
        if ($task->isClaimed()) {
            throw new \RuntimeException(sprintf('User task %s is already claimed by %s', $task->getId(), $task->getAssignee()));
        }
        
        $sql = "
            UPDATE `#__bpmn_user_task`
            SET `claimed_at` = :time,
                `claimed_by` = :assignee
            WHERE `id` = :id
        ";
        $stmt = $engine->prepareQuery($sql);
        $stmt->bindValue('time', time());
        $stmt->bindValue('assignee', $this->assignee);
        $stmt->bindValue('id', $task->getId());
        $stmt->execute();
        
        $task = $engine->getTaskService()->createTaskQuery()->taskId($this->taskId)->findOne();
        
        $engine->notify(new UserTaskClaimedEvent($task, $engine));
        
        $engine->debug('User task "{task}" claimed by {assignee}', [
            'task' => $task->getName(),
            'assignee' => $this->assignee
        ]);
    }
}
