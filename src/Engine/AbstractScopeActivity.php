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
 * Base class for activities that have a scope and can host boundary activities.
 * 
 * @author Martin Schröder
 */
abstract class AbstractScopeActivity extends AbstractActivity
{
	/**
	 * {@inheritdoc}
	 */
	public function createEventSubscriptions(VirtualExecution $execution, $activityId, Node $node = NULL)
	{
		parent::createEventSubscriptions($execution, $activityId, $node);
		
		foreach($this->findAttachedBoundaryActivities($execution) as $node)
		{
			$behavior = $node->getBehavior();
				
			if($behavior instanceof AbstractBoundaryActivity)
			{
				$behavior->createEventSubscriptions($execution, $activityId, $node);
			}
		}
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function clearEventSubscriptions(VirtualExecution $execution, $activityId)
	{
		parent::clearEventSubscriptions($execution, $activityId);
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
	protected function leaveConcurrent(VirtualExecution $execution, Node $node = NULL, array $transitions = NULL)
	{
		if($execution->isConcurrent())
		{
			$root = $execution->getParentExecution();
		}
		else
		{
			$root = $execution->introduceConcurrentRoot();
		}
			
		$exec = $root->createExecution(true);
		$exec->setNode(($node === NULL) ? $execution->getNode() : $node);
		
		$exec->getEngine()->syncExecutions();
		
		$exec->takeAll($transitions);
		
		return $exec;
	}
	
	/**
	 * Collect all boundary events connected to the activity of the given execution.
	 *
	 * @param VirtualExecution $execution
	 * @return array<Node>
	 */
	protected function findAttachedBoundaryActivities(VirtualExecution $execution)
	{
		$model = $execution->getProcessModel();
		$ref = ($execution->getNode() === NULL) ? NULL : $execution->getNode()->getId();
		$activities = [];
	
		foreach($model->findNodes() as $node)
		{
			$behavior = $node->getBehavior();
	
			if($behavior instanceof AbstractBoundaryActivity)
			{
				if($ref == $behavior->getAttachedTo())
				{
					$activities[] = $node;
				}
			}
		}
	
		return $activities;
	}
}
