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
 * Receive task that acts as a wait state until being triggered by a signal.
 * 
 * The wait must be triggered using eighter Execution::signal() or RuntimeService::signal().
 * 
 * @author Martin Schröder
 */
class ReceiveTaskBehavior extends AbstractScopeActivity
{
    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $execution->waitForSignal();
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        if ($signal !== null) {
            throw new \RuntimeException(\sprintf('Receive task only supports null-signals, given "%s"', $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        $engine = $execution->getEngine();
        $name = $this->getStringValue($this->name, $execution->getExpressionContext());
        
        $engine->debug('Triggered receive task "{task}"', [
            'task' => $name
        ]);
        
        $engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
        
        $this->leave($execution);
    }
}
