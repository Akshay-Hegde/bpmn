<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\History\Event;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;

/**
 * Is triggered whenever an execution has been created. 
 * 
 * @author Martin Schröder
 */
class ExecutionCreatedEvent extends AbstractAuditEvent
{
    /**
     * The created execution.
     * 
     * @var VirtualExecution
     */
    public $execution;

    /**
     * Execution variables to be recorded.
     *
     * @var array
     */
    public $variables;

    public function __construct(VirtualExecution $execution, array $variables, ProcessEngine $engine)
    {
        parent::__construct($engine);
        
        $this->execution = $execution;
        $this->variables = $variables;
    }
}
