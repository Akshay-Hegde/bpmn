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
 * Have an execution take one or multiple transitions out of a node.
 * 
 * @author Martin Schröder
 */
class AsyncAfterHandler implements JobHandlerInterface
{
	/**
	 * Name of the job handler.
	 * 
	 * @var string
	 */
	const HANDLER_TYPE = 'async-after';
	
	/**
	 * ID of the node to transition out of.
	 * 
	 * @var string
	 */
	const PARAM_NODE_ID = 'nodeId';
	
	/**
	 * Holds identifiers of all outgoing transitions to be taken, or NULL in order to take all transitions.
	 * 
	 * @var array
	 */
	const PARAM_TRANSITIONS = 'transitions';
	
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
		$transitions = $data[self::PARAM_TRANSITIONS];
		
		$engine->debug('Async continuation started after {node} using {execution}', [
			'node' => $node->getId(),
			'execution' => (string)$execution
		]);
		
		$execution->takeAll($transitions);
	}
}
