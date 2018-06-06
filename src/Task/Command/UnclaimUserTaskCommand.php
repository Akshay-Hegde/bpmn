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
use KoolKode\BPMN\Task\Event\UserTaskUnclaimedEvent;
use KoolKode\Util\UUID;

/**
 * Removes the current assignee from a user task.
 * 
 * @author Martin Schröder
 */
class UnclaimUserTaskCommand extends AbstractBusinessCommand
{
    protected $taskId;

    public function __construct(UUID $taskId)
    {
        $this->taskId = $taskId;
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
        
        if (!$task->isClaimed()) {
            throw new \RuntimeException(\sprintf('User task %s is not claimed', $task->getId()));
        }
        
        $sql = "
            UPDATE `#__bpmn_user_task`
            SET `claimed_at` = :time,
                `claimed_by` = :assignee
            WHERE `id` = :id
        ";
        $stmt = $engine->prepareQuery($sql);
        $stmt->bindValue('time', null);
        $stmt->bindValue('assignee', null);
        $stmt->bindValue('id', $task->getId());
        $stmt->execute();
        
        $task = $engine->getTaskService()->createTaskQuery()->taskId($this->taskId)->findOne();
        
        $engine->notify(new UserTaskUnclaimedEvent($task, $engine));
        
        $engine->debug('User task "{task}" unclaimed', [
            'task' => $task->getName()
        ]);
    }
}
