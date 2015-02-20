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
use KoolKode\BPMN\Runtime\Event\CheckpointReachedEvent;

/**
 * Notifies event listeners when a checkpoint within a process has been reached.
 * 
 * @author Martin Schröder
 */
class NotifyCheckpointCommand extends AbstractBusinessCommand
{
	protected $name;
	
	protected $executionId;
	
	public function __construct($name, VirtualExecution $execution)
	{
		$this->name = (string)$name;
		$this->executionId = $execution->getId();
	}
	
	/**
	 * {@inheritdoc}
	 *
	 * @codeCoverageIgnore
	 */
	public function isSerializable()
	{
		return true;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function executeCommand(ProcessEngine $engine)
	{
		$engine->syncExecutions();
		
		$exec = $engine->getRuntimeService()->createExecutionQuery()->executionId($this->executionId)->findOne();
		$execution = $engine->findExecution($this->executionId);
		
		$engine->debug('{execution} reached checkpoint "{checkpoint}" ({node})', [
			'execution' => (string)$execution,
			'checkpoint' => $this->name,
			'node' => $execution->getNode()->getId()
		]);
		
		$engine->notify(new CheckpointReachedEvent($this->name, $exec, $engine));
		
		$execution->signal();
	}
}
