<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Engine\AbstractScopeActivity;
use KoolKode\BPMN\Engine\VirtualExecution;

/**
 * Executes an embedded sub process within a child execution with shared variable scope.
 * 
 * @author Martin Schröder
 */
class SubProcessBehavior extends AbstractScopeActivity
{
    protected $startNodeId;

    public function __construct(string $activityId, string $startNodeId)
    {
        parent::__construct($activityId);
        
        $this->startNodeId = $startNodeId;
    }

    public function getId(): string
    {
        return $this->activityId;
    }

    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $model = $execution->getProcessModel();
        
        $execution->getEngine()->debug('Starting sub process "{process}"', [
            'process' => $this->getStringValue($this->name, $execution->getExpressionContext())
        ]);
        
        $startNode = $model->findNode($this->startNodeId);
        
        if (!$startNode->getBehavior() instanceof NoneStartEventBehavior) {
            throw new \RuntimeException(\vsprintf('Cannot start sub process %s ("%s") because it is missing start node %s', [
                $execution->getNode()->getId(),
                $this->getStringValue($this->name, $execution->getExpressionContext()),
                $this->startNodeId
            ]));
        }
        
        $execution->waitForSignal();
        
        $sub = $execution->createNestedExecution($model, $startNode, true, false);
        
        $sub->execute($startNode);
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        if ($this->delegateSignal($execution, $signal, $variables, $delegation)) {
            return;
        }
        
        if (empty($delegation['executionId'])) {
            $execution->terminate(false);
            
            return;
        }
        
        $sub = $execution->getEngine()->findExecution($delegation['executionId']);
        
        if (!$sub instanceof VirtualExecution) {
            throw new \RuntimeException(\sprintf('Missing nested execution being signaled'));
        }
        
        $execution->getEngine()->debug('Resuming {execution} after sub process "{process}"', [
            'execution' => (string) $execution,
            'process' => $this->getStringValue($this->name, $execution->getExpressionContext())
        ]);
        
        $this->leave($execution);
    }

    /**
     * {@inheritdoc}
     */
    public function interrupt(VirtualExecution $execution, ?array $transitions = null): void
    {
        foreach ($execution->findChildExecutions() as $sub) {
            $sub->terminate(false);
        }
        
        parent::interrupt($execution, $transitions);
    }
}
