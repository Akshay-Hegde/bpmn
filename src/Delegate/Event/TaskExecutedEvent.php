<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Delegate\Event;

use KoolKode\BPMN\Delegate\DelegateExecutionInterface;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\ProcessEngineEvent;

/**
 * Is triggered whenever any kind of task has been executed (even manual or generic tasks).
 * 
 * @author Martin Schröder
 */
class TaskExecutedEvent extends ProcessEngineEvent
{
    /**
     * Name of the task being executed.
     * 
     * @var string
     */
    public $name;

    /**
     * Provides access to the execution that triggered the service task.
     * 
     * @var DelegateExecutionInterface
     */
    public $execution;

    public function __construct(string $name, DelegateExecutionInterface $execution, ProcessEngine $engine)
    {
        $this->name = $name;
        $this->execution = $execution;
        $this->engine = $engine;
    }
}
