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

use KoolKode\BPMN\History\Event\ActivityCompletedEvent;
use KoolKode\BPMN\History\Event\ActivityStartedEvent;
use KoolKode\Process\Node;

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
     * @var bool
     */
    protected $interrupting = true;

    public function __construct(string $activityId, string $attachedTo)
    {
        $this->activityId = $activityId;
        $this->attachedTo = $attachedTo;
    }

    public function getActivityId(): string
    {
        return $this->activityId;
    }

    public function getAttachedTo(): string
    {
        return $this->attachedTo;
    }

    public function isInterrupting(): bool
    {
        return $this->interrupting;
    }

    public function setInterrupting(bool $interrupting): void
    {
        $this->interrupting = $interrupting ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        $engine = $execution->getEngine();
        
        $node = $execution->getProcessModel()->findNode($this->activityId);
        $name = '';
        
        if ($node->getBehavior() instanceof AbstractActivity) {
            $name = $node->getBehavior()->getName($execution->getExpressionContext()) ?? '';
        }
        
        // Log activity, boundary events do not have a duration > 0.
        $engine->notify(new ActivityStartedEvent($this->activityId, $name, $execution, $engine));
        $engine->notify(new ActivityCompletedEvent($this->activityId, $execution, $engine));
        
        if ($this->isInterrupting()) {
            $this->findScopeActivity($execution)->interrupt($execution);
        } else {
            $this->findScopeActivity($execution)->leaveConcurrent($execution);
        }
    }
    
    public function findScopeNode(VirtualExecution $execution): Node
    {
        return $execution->getProcessModel()->findNode($this->attachedTo);
    }
    
    public function findScopeActivity(VirtualExecution $execution): AbstractScopeActivity
    {
        return $execution->getProcessModel()->findNode($this->attachedTo)->getBehavior();
    }
}
