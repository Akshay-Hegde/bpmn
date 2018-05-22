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

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Task\Command\ClaimUserTaskCommand;
use KoolKode\BPMN\Task\Command\CompleteUserTaskCommand;
use KoolKode\BPMN\Task\Command\CreateUserTaskCommand;
use KoolKode\BPMN\Task\Command\RemoveUserTaskCommand;
use KoolKode\BPMN\Task\Command\UnclaimUserTaskCommand;
use KoolKode\Util\UUID;

class TaskService
{
    protected $engine;

    public function __construct(ProcessEngine $engine)
    {
        $this->engine = $engine;
    }

    public function createTaskQuery(): TaskQuery
    {
        return new TaskQuery($this->engine);
    }

    public function createTask(string $name, int $priority = 0, ?string $documentation = null): TaskInterface
    {
        return $this->engine->executeCommand(new CreateUserTaskCommand($name, $priority, null, $documentation));
    }

    public function claim(UUID $taskId, string $userId): void
    {
        $this->engine->pushCommand(new ClaimUserTaskCommand($taskId, $userId));
    }

    public function unclaim(UUID $taskId): void
    {
        $this->engine->pushCommand(new UnclaimUserTaskCommand($taskId));
    }

    public function complete(UUID $taskId, array $variables = []): void
    {
        $this->engine->pushCommand(new CompleteUserTaskCommand($taskId, $variables));
    }

    public function removeTask(UUID $taskId): void
    {
        $this->engine->pushCommand(new RemoveUserTaskCommand($taskId));
    }
}
