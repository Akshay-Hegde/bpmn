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

namespace KoolKode\BPMN\Delegate\Behavior;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeActivity;
use KoolKode\BPMN\Engine\VirtualExecution;

/**
 * Generic task behavior that triggers an event and proceeds with the process.
 * 
 * @author Martin Schröder
 */
class TaskBehavior extends AbstractScopeActivity
{
    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $engine = $execution->getEngine();
        $name = $this->getStringValue($this->name, $execution->getExpressionContext());
        
        $execution->getEngine()->debug('Executing manual task "{task}"', [
            'task' => $name
        ]);
        
        $engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
        
        $this->leave($execution);
    }
}
