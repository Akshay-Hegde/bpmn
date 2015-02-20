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

/**
 * Base class for BPMN boundary events.
 * 
 * @author Martin Schröder
 */
abstract class AbstractBoundaryActivity extends AbstractActivity
{
	/**
	 * ID of the boundary event or event sub process node.
	 * 
	 * @var string
	 */
	protected $activityId;
	
	/**
	 * ID of the scope node that this boundary activity is attached to.
	 * 
	 * @var string
	 */
	protected $attachedTo;
	
	/**
	 * Is this activity interrupting?
	 * 
	 * @var boolean
	 */
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
	public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
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
}
