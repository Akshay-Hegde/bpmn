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
 * Is triggered whenever an activity is being canceled. 
 * 
 * @author Martin Schröder
 */
class ActivityCanceledEvent extends AbstractAuditEvent
{
    /**
     * ID of the activity / scope being completed.
     * 
     * @var string
     */
    public $name;

    /**
     * The related execution.
     * 
     * @var VirtualExecution
     */
    public $execution;

    public function __construct(string $name, VirtualExecution $execution, ProcessEngine $engine)
    {
        parent::__construct($engine);
        
        $this->name = $name;
        $this->execution = $execution;
    }
}
