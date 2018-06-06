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

namespace KoolKode\BPMN\History\Event;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;

/**
 * Is triggered whenever an execution has been terminated. 
 * 
 * @author Martin Schröder
 */
class ExecutionTerminatedEvent extends AbstractAuditEvent
{
    /**
     * The terminated execution.
     * 
     * @var VirtualExecution
     */
    public $execution;

    public function __construct(VirtualExecution $execution, ProcessEngine $engine)
    {
        parent::__construct($engine);
        
        $this->execution = $execution;
    }
}
