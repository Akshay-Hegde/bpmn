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
	
	public function __construct(VirtualExecution $execution, ProcessEngine $engine)
	{
		$this->execution = $execution;
		$this->timestamp = new \DateTimeImmutable();
		$this->engine = $engine;
	}
	
	public function isProcessInstance()
	{
		return $this->execution->isRootExecution();
	}
}
