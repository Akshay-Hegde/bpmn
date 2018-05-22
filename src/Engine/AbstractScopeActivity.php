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

use KoolKode\BPMN\History\Event\ActivityCanceledEvent;
use KoolKode\BPMN\History\Event\ActivityCompletedEvent;
use KoolKode\BPMN\History\Event\ActivityStartedEvent;
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

    public function __construct(string $activityId)
    {
        $this->activityId = $activityId;
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Execution $execution): void
    {
        $execution->getEngine()->notify(new ActivityStartedEvent($this->activityId, $execution, $execution->getEngine()));
        
        $execution->getEngine()->info('ENTER: scope <{scope}> at level {level} using {execution}', [
            'scope' => $this->activityId,
            'level' => $execution->getExecutionDepth(),
            'execution' => (string) $execution
        ]);
        
        $root = $execution->createNestedExecution($execution->getProcessModel(), $execution->getNode(), true);
        $root->waitForSignal();
        
        $this->createEventSubscriptions($root, $this->activityId, $execution->getNode());
        
        $this->enter($root->createExecution(false));
    }

    /**
     * {@inheritdoc}
     */
    public function createEventSubscriptions(VirtualExecution $execution, string $activityId, Node $node = null): void
    {
        parent::createEventSubscriptions($execution, $this->activityId, $node);
        
        foreach ($this->findAttachedBoundaryActivities($execution) as $node) {
            $behavior = $node->getBehavior();
            
            if ($behavior instanceof AbstractBoundaryActivity) {
                $behavior->createEventSubscriptions($execution, $this->activityId, $node);
            }
        }
    }

    /**
     * Interrupt the scope activity.
     */
    public function interrupt(VirtualExecution $execution, ?array $transitions = null): void
    {
        $this->leave($execution, $transitions, true);
    }

    /**
     * {@inheritdoc}
     */
    public function leave(VirtualExecution $execution, ?array $transitions = null, bool $canceled = false): void
    {
        $root = $execution->getScope();
        
        $this->clearEventSubscriptions($root, $this->activityId);
        
        // Fetch outer execution and move it to target node before transition.
        $outer = $root->getParentExecution();
        $outer->setNode($execution->getNode());
        
        $root->terminate(false);
        
        if ($canceled) {
            $execution->getEngine()->notify(new ActivityCanceledEvent($this->activityId, $outer, $outer->getEngine()));
        }
        
        $execution->getEngine()->notify(new ActivityCompletedEvent($this->activityId, $outer, $outer->getEngine()));
        
        $execution->getEngine()->info('LEAVE: scope <{scope}> at level {level} using {execution}', [
            'scope' => $this->activityId,
            'level' => $outer->getExecutionDepth(),
            'execution' => (string) $outer
        ]);
        
        $outer->takeAll($transitions);
    }

    /**
     * Create a new execution concurrent to the given execution and have it take the given transitions.
     * 
     * If the given execution is concurrent this method will create a new child execution from the parent execution.
     * Otherwise a new concurrent root will be introduced as parent of the given execution.
     */
    public function leaveConcurrent(VirtualExecution $execution, ?Node $node = null, ?array $transitions = null): VirtualExecution
    {
        $root = $execution->getScope();
        $parent = $root->getParentExecution();
        
        $exec = $parent->createExecution(true);
        $exec->setNode(($node === null) ? $execution->getNode() : $node);
        
        $root->setConcurrent(true);
        
        $this->createEventSubscriptions($root, $this->activityId, $execution->getProcessModel()->findNode($this->activityId));
        
        $exec->takeAll($transitions);
        
        return $exec;
    }

    /**
     * Collect all boundary events connected to the activity of the given execution.
     */
    public function findAttachedBoundaryActivities(VirtualExecution $execution): array
    {
        $model = $execution->getProcessModel();
        $activities = [];
        
        foreach ($model->findNodes() as $node) {
            $behavior = $node->getBehavior();
            
            if ($behavior instanceof AbstractBoundaryActivity) {
                if ($this->activityId == $behavior->getAttachedTo()) {
                    $activities[] = $node;
                }
            }
        }
        
        return $activities;
    }
}
