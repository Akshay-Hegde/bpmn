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
use KoolKode\BPMN\Engine\ProcessEngineEvent;

/**
 * Base class for all history / audit events.
 * 
 * @author Martin Schröder
 */
abstract class AbstractAuditEvent extends ProcessEngineEvent
{
    /**
     * Timestamp of the audit event.
     * 
     * @var \DateTimeImmutable
     */
    public $timestamp;

    /**
     * Create a new audi event (timestamp is initialized here).
     * 
     * @param ProcessEngine $engine
     * @param boolean $delay Delay creation of timestamp by 1 millisecond?
     */
    public function __construct(ProcessEngine $engine, ?bool $delay = false)
    {
        if ($delay) {
            \usleep(1000);
        }
        
        $this->engine = $engine;
        $this->timestamp = \DateTimeImmutable::createFromFormat('U.u', \sprintf('%0.3f', \microtime(true)));
    }
}
