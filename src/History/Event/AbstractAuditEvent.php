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
	 * @var \DateTimeInterface
	 */
	public $timestamp;
}
