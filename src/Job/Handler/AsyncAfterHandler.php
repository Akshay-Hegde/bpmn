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
	const HANDLER_TYPE = 'async-after';
	
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
		$transitions = $data['transitions'];
		
		$engine->debug('Async continuation started after {node} using {execution}', [
			'node' => $node->getId(),
			'execution' => (string)$execution
		]);
		
		$execution->takeAll($transitions);
	}
}
