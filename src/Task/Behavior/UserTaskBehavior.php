<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Task\Behavior;

use KoolKode\BPMN\Engine\AbstractScopeActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Task\Command\ClaimUserTaskCommand;
use KoolKode\BPMN\Task\Command\CreateUserTaskCommand;
use KoolKode\BPMN\Task\Command\RemoveUserTaskCommand;
use KoolKode\Expression\ExpressionInterface;
use KoolKode\Database\UUIDTransformer;

/**
 * Creates user tasks and waits for their completion.
 * 
 * @author Martin Schröder
 */
class UserTaskBehavior extends AbstractScopeActivity
{
    protected $assignee;

    protected $priority;

    protected $dueDate;

    public function setAssignee(?ExpressionInterface $assignee): void
    {
        $this->assignee = $assignee;
    }

    public function setPriority(?ExpressionInterface $priority): void
    {
        $this->priority = $priority;
    }

    public function setDueDate(?ExpressionInterface $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $context = $execution->getExpressionContext();
        $command = new CreateUserTaskCommand($this->getStringValue($this->name, $context), (int) $this->getIntegerValue($this->priority, $context), $execution, $this->getStringValue($this->documentation, $context));
        
        if (null !== ($due = $this->getDateValue($this->dueDate, $context))) {
            $command->setDueDate($due);
        }
        
        $task = $execution->getEngine()->executeCommand($command);
        
        if ($this->assignee !== null) {
            $execution->getEngine()->pushCommand(new ClaimUserTaskCommand($task->getId(), $this->getStringValue($this->assignee, $context)));
        }
        
        $execution->waitForSignal();
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        if ($this->delegateSignal($execution, $signal, $variables, $delegation)) {
            return;
        }
        
        if ($signal !== 'user-task') {
            throw new \RuntimeException(\sprintf('User task cannot process signal "%s"', $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        $this->leave($execution);
    }

    /**
     * {@inheritdoc}
     */
    public function interrupt(VirtualExecution $execution, ?array $transitions = null): void
    {
        $engine = $execution->getEngine();
        $root = $execution->getScope();
        
        $params = [
            'e1' => $root->getId()
        ];
        
        $i = 1;
        foreach ($root->findChildExecutions() as $child) {
            $params['e' . ++$i] = $child->getId();
        }
        
        $placeholders = \implode(', ', \array_map(function ($p) {
            return ':' . $p;
        }, \array_keys($params)));
        
        $stmt = $engine->prepareQuery("SELECT `id` FROM `#__bpmn_user_task` WHERE `execution_id` IN ($placeholders)");
        $stmt->bindAll($params);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->execute();
        
        foreach ($stmt->fetchColumns('id') as $taskId) {
            $engine->executeCommand(new RemoveUserTaskCommand($taskId));
        }
        
        parent::interrupt($execution, $transitions);
    }
}
