<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Job\Handler;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Job\Job;
use KoolKode\Process\Command\ExecuteNodeCommand;

/**
 * Have the engine move an execution into a target node and execute it's behavior.
 * 
 * @author Martin Schröder
 */
class AsyncContinuationHandler implements JobHandlerInterface
{
	const HANDLER_TYPE = 'async-continuation';
	
	/**
	 * {@inheritdoc}
	 */
	public function getType()
	{
		return self::HANDLER_TYPE;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function executeJob(Job $job, VirtualExecution $execution, ProcessEngine $engine)
	{
		$data = (array)$job->getHandlerData();
		
		$node = $execution->getProcessModel()->findNode($data['nodeId']);
		
		$engine->pushCommand(new ExecuteNodeCommand($execution, $node));
	}
}
