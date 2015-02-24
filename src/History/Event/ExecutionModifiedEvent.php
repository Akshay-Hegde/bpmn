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
 * Is triggered during sync whenever an execution has been modified.
 * 
 * @author Martin Schröder
 */
class ExecutionModifiedEvent extends AbstractAuditEvent
{
	/**
	 * The created execution.
	 * 
	 * @var VirtualExecution
	 */
	public $execution;
	
	/**
	 * Internal execution state / flags.
	 * 
	 * @var integer
	 */
	public $state;
	
	/**
	 * Execution variables to be recorded.
	 * 
	 * @var array
	 */
	public $variables;
	
	public function __construct(VirtualExecution $execution, $state, array $variables, ProcessEngine $engine)
	{
		parent::__construct($engine);
		
		$this->execution = $execution;
		$this->state = (int)$state;
		$this->variables = $variables;
	}
}
