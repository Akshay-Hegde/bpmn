<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

use KoolKode\Process\Node;
/**
 * Base class for BPMN boundary events.
 * 
 * @author Martin Schröder
 */
abstract class AbstractBoundaryActivity extends AbstractActivity
{
	protected $activityId;
	
	protected $attachedTo;
	
	protected $interrupting = true;
	
	public function __construct($activityId, $attachedTo)
	{
		$this->activityId = (string)$activityId;
		$this->attachedTo = (string)$attachedTo;
	}
	
	public function getActivityId()
	{
		return $this->activityId;
	}
	
	public function getAttachedTo()
	{
		return $this->attachedTo;
	}
	
	public function isInterrupting()
	{
		return $this->interrupting;
	}
	
	public function setInterrupting($interrupting)
	{
		$this->interrupting = $interrupting ? true : false;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function processSignal(VirtualExecution $execution, $signal = NULL, array $variables = [])
	{
		if($this->isInterrupting())
		{
			$this->findScopeActivity($execution)->interrupt($execution);
		}
		else
		{
			$this->findScopeActivity($execution)->leaveConcurrent($execution);
		}
	}
	
	/**
	 * @param VirtualExecution $execution
	 * @return Node
	 */
	public function findScopeNode(VirtualExecution $execution)
	{
		return $execution->getProcessModel()->findNode($this->attachedTo);
	}
	
	/**
	 * @param VirtualExecution $execution
	 * @return AbstractScopeActivity
	 */
	public function findScopeActivity(VirtualExecution $execution)
	{
		return $execution->getProcessModel()->findNode($this->attachedTo)->getBehavior();
	}
	
	/**
	 * Find the execution that actvated the scope.
	 *
	 * @param VirtualExecution $execution
	 * @return VirtualExecution
	 */
	protected function findScopeExecution(VirtualExecution $execution)
	{
		$exec = $execution;
		
		while($exec->isConcurrent())
		{
			$exec = $exec->getParentExecution();
		}
	
		return $exec;
	}
}
