<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;
use KoolKode\Process\Command\SignalExecutionCommand;

/**
 * Notifies event listeners when a message throw event has been executed.
 * 
 * @author Martin Schröder
 */
class ThrowMessageCommand extends AbstractBusinessCommand
{
	protected $executionId;
	
	public function __construct(VirtualExecution $execution)
	{
		$this->executionId = $execution->getId();
	}
	
	public function isSerializable()
	{
		return true;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT - 500;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		// Need to sync here to preserve state at current node in execution loaded via query.
		$engine->syncExecutions();
		
		$execution = $engine->getRuntimeService()
							->createExecutionQuery()
							->executionId($this->executionId)
							->findOne();
		
		// Signal execution to continue creating subscriptions etc...
		$engine->pushCommand(new SignalExecutionCommand($engine->findExecution($this->executionId)));
		
		// Notifications should be domain events that are executed outside a transaction!
		$engine->notify(new MessageThrownEvent($execution, $engine));
	}
}
