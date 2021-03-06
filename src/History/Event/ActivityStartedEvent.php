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
 * Is triggered whenever an execution enters an activity. 
 * 
 * @author Martin Schröder
 */
class ActivityStartedEvent extends AbstractAuditEvent
{
    /**
     * ID of the activity / scope being started.
     * 
     * @var string
     */
    public $name;
    
    /**
     * Title of the started activity.
     * 
     * @var string
     */
    public $title;

    /**
     * The related execution.
     * 
     * @var VirtualExecution
     */
    public $execution;

    public function __construct(string $name, string $title, VirtualExecution $execution, ProcessEngine $engine)
    {
        parent::__construct($engine, true);
        
        $this->name = $name;
        $this->title = $title;
        $this->execution = $execution;
    }
}
