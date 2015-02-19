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

use KoolKode\Process\Execution;
use KoolKode\Process\Node;

/**
 * Base class for activities that have a scope and can host boundary activities.
 * 
 * @author Martin Schröder
 */
abstract class AbstractScopeActivity extends AbstractActivity
{
	protected $activityId;
	
	public function __construct($activityId)
	{
		$this->activityId = (string)$activityId;
	}
	
	public function getActivityId()
	{
		return $this->activityId;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function execute(Execution $execution)
	{
		$root = $execution->createNestedExecution($execution->getProcessModel(), true);
		$root->setNode($execution->getNode());
		$root->setActive(false);
		$root->waitForSignal();
		
		$this->createEventSubscriptions($root, $this->activityId, $execution->getNode());
		
		$this->enter($root->createExecution(false));
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function createEventSubscriptions(VirtualExecution $execution, $activityId, Node $node = NULL)
	{
		parent::createEventSubscriptions($execution, $this->activityId, $node);
		
		foreach($this->findAttachedBoundaryActivities($execution) as $node)
		{
			$behavior = $node->getBehavior();
				
			if($behavior instanceof AbstractBoundaryActivity)
			{
				$behavior->createEventSubscriptions($execution, $this->activityId, $node);
			}
		}
	}
	
	/**
	 * Interrupt the scope activity.
	 * 
	 * @param VirtualExecution $execution
	 * @param array $transitions
	 */
	public function interrupt(VirtualExecution $execution, array $transitions = NULL)
	{
		$this->leave($execution, $transitions);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function leave(VirtualExecution $execution, array $transitions = NULL)
	{		
		$root = $execution->getScope();
		
		$this->clearEventSubscriptions($root, $this->activityId);
		
		// Fetch outer execution and move it to target node before transition.
		$outer = $root->getParentExecution();
		$outer->setActive(true);
		$outer->setNode($execution->getNode());
		
		$root->terminate(false);
	
		$outer->takeAll($transitions);
	}
	
	/**
	 * Create a new execution concurrent to the given execution and have it take the given transitions.
	 * 
	 * If the given execution is concurrent this method will create a new child execution from the parent execution.
	 * Otherwise a new concurrent root will be introduced as parent of the given execution.
	 * 
	 * @param VirtualExecution $execution
	 * @param Node $node
	 * @param array $transitions
	 * @return VirtualExecution The new concurrent execution created by this method.
	 */
	public function leaveConcurrent(VirtualExecution $execution, Node $node = NULL, array $transitions = NULL)
	{
		if($execution->isConcurrent())
		{
			$root = $execution->getParentExecution();
		}
		else
		{
			$root = $execution;
		}
		
		$exec = $root->createExecution(true);
		$exec->setNode(($node === NULL) ? $execution->getNode() : $node);
		
		$this->createEventSubscriptions($root, $this->activityId, $execution->getProcessModel()->findNode($this->activityId));
		
		$exec->takeAll($transitions);
		
		return $exec;
	}
	
	/**
	 * Collect all boundary events connected to the activity of the given execution.
	 *
	 * @param VirtualExecution $execution
	 * @return array<Node>
	 */
	public function findAttachedBoundaryActivities(VirtualExecution $execution)
	{
		$model = $execution->getProcessModel();
		$activities = [];
	
		foreach($model->findNodes() as $node)
		{
			$behavior = $node->getBehavior();
	
			if($behavior instanceof AbstractBoundaryActivity)
			{
				if($this->activityId == $behavior->getAttachedTo())
				{
					$activities[] = $node;
				}
			}
		}
	
		return $activities;
	}
}
