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
use KoolKode\Process\Command\TakeTransitionCommand;

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
	 * Identifier of the transition to be taken or NULL when taking the only outgoing transition.
	 * 
	 * @var string
	 */
	const PARAM_TRANSITION = 'transition';
	
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
		if($execution->isTerminated())
		{
			throw new \RuntimeException(sprintf('%s is terminated', $execution));
		}
		
		$data = (array)$job->getHandlerData();
		
		$node = $execution->getProcessModel()->findNode($data[self::PARAM_NODE_ID]);
		$trans = $data[self::PARAM_TRANSITION];
		
		if($trans !== NULL)
		{
			$trans = $execution->getProcessModel()->findTransition($trans);
		}
		
		// Move execution into async start node.
		$execution->setNode($node);
		
		$engine->debug('Async continuation started after {node} using {execution}', [
			'node' => $node->getId(),
			'execution' => (string)$execution
		]);
		
		// Push transition command to avoid re-entering async-after via process engine.
		$engine->pushCommand(new TakeTransitionCommand($execution, $trans));
	}
}
