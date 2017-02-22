<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Engine\AbstractBoundaryActivity;
use KoolKode\BPMN\Engine\ActivityInterface;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\History\Event\ActivityCanceledEvent;
use KoolKode\Process\Node;

/**
 * Executes an embedded sub process within a child execution with shared variable scope.
 * 
 * @author Martin Schröder
 */
class EventSubProcessBehavior extends AbstractBoundaryActivity
{
    protected $startNodeId;

    public function __construct($activityId, $attachedTo, $startNodeId)
    {
        parent::__construct($activityId, $attachedTo);
        
        $this->startNodeId = (string) $startNodeId;
    }

    public function createEventSubscriptions(VirtualExecution $execution, $activityId, Node $node = null)
    {
        $startNode = $execution->getProcessModel()->findNode($this->startNodeId);
        $behavior = $startNode->getBehavior();
        
        if (!$behavior instanceof ActivityInterface) {
            throw new \InvalidArgumentException(sprintf('Start node %s cannot subscribe to event', $startNode->getId()));
        }
        
        $behavior->createEventSubscriptions($execution, $activityId, $execution->getProcessModel()->findNode($this->activityId));
    }

    public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
    {
        $startNode = $execution->getProcessModel()->findNode($this->startNodeId);
        
        $root = $execution->getScope();
        $scope = $this->findScopeActivity($execution);
        
        if (!$this->isInterrupting()) {
            return $scope->leaveConcurrent($root, $startNode);
        }
        
        // Kill all remaining concurrent executions within the scope activity:
        foreach ($root->findChildExecutions() as $child) {
            $child->terminate(false);
        }
        
        $scope->clearEventSubscriptions($root, $scope->getActivityId());
        
        $root->setNode($this->findScopeNode($root));
        $root->setActive(false);
        $root->waitForSignal();
        
        $sub = $root->createExecution(false);
        $sub->setNode($startNode);
        $sub->waitForSignal();
        
        $sub->getEngine()->notify(new ActivityCanceledEvent($this->attachedTo, $sub, $sub->getEngine()));
        
        // Delegate received signal to event sub process start event.
        $sub->signal($signal, $variables);
    }
}
