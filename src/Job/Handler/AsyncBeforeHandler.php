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

/**
 * Have the engine move an execution into a target node and execute it's behavior.
 * 
 * @author Martin Schröder
 */
class AsyncBeforeHandler implements JobHandlerInterface
{
	/**
	 * Name of the job handler.
	 * 
	 * @var string
	 */
	const HANDLER_TYPE = 'async-before';
	
	/**
	 * ID of the node to be executed.
	 * 
	 * @var string
	 */
	const PARAM_NODE_ID = 'nodeId';
	
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
		
		$node = $execution->getProcessModel()->findNode($data[self::PARAM_NODE_ID]);
		
		$engine->debug('Async continuation started before {node} using {execution}', [
			'node' => $node->getId(),
			'execution' => (string)$execution
		]);
		
		$execution->execute($node);
	}
}
