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

namespace KoolKode\BPMN\Task\Event;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\History\Event\AbstractAuditEvent;
use KoolKode\BPMN\Task\TaskInterface;

/**
 * Is triggered whenever a user task has been claimed.
 * 
 * @author Martin Schröder
 */
class UserTaskClaimedEvent extends AbstractAuditEvent
{
    /**
     * The task being claimed.
     * 
     * @var TaskInterface
     */
    public $task;

    public function __construct(TaskInterface $task, ProcessEngine $engine)
    {
        parent::__construct($engine);
        
        $this->task = $task;
    }
}
