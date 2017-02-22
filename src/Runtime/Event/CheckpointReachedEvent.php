<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Runtime\Event;

use KoolKode\BPMN\Delegate\DelegateExecutionInterface;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\ProcessEngineEvent;

/**
 * Is triggered whenever a specific checkpoint within a process has been reached.
 * 
 * @author Martin Schröder
 */
class CheckpointReachedEvent extends ProcessEngineEvent
{
    /**
     * Name of the element that triggered the checkpoint.
     * 
     * @var string
     */
    public $name;

    /**
     * The execution throwing the message.
     * 
     * @var DelegateExecutionInterface
     */
    public $execution;

    public function __construct($name, DelegateExecutionInterface $execution, ProcessEngine $engine)
    {
        $this->name = (string) $name;
        $this->execution = $execution;
        $this->engine = $engine;
    }
}
