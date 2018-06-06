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

use KoolKode\BPMN\Engine\AbstractScopeActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Expression\ExpressionInterface;

/**
 * Executes an external process within a child execution with isolated variable scope.
 * 
 * @author Martin Schröder
 */
class CallActivityBehavior extends AbstractScopeActivity
{
    protected $processDefinitionKey;

    protected $inputs = [];

    protected $outputs = [];

    public function __construct(string $activityId, string $processDefinitionKey)
    {
        parent::__construct($activityId);
        
        $this->processDefinitionKey = $processDefinitionKey;
    }

    public function addInput(string $target, $source): void
    {
        if ($source instanceof ExpressionInterface) {
            $this->inputs[$target] = $source;
        } else {
            $this->inputs[$target] = (string) $source;
        }
    }

    public function addOutput(string $target, $source): void
    {
        if ($source instanceof ExpressionInterface) {
            $this->outputs[$target] = $source;
        } else {
            $this->outputs[$target] = (string) $source;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $context = $execution->getExpressionContext();
        $definition = $execution->getEngine()->getRepositoryService()->createProcessDefinitionQuery()->processDefinitionKey($this->processDefinitionKey)->findOne();
        
        $execution->getEngine()->debug('Starting process {process} from call activity "{task}"', [
            'process' => $this->processDefinitionKey,
            'task' => $this->getStringValue($this->name, $context)
        ]);
        
        $start = $definition->getModel()->findInitialNodes();
        
        if (\count($start) !== 1) {
            throw new \RuntimeException(\sprintf('Missing single non start event in process %s', $definition->getKey()));
        }
        
        $startNode = \array_shift($start);
        
        $sub = $execution->createNestedExecution($definition->getModel(), $startNode, true, true);
        
        foreach ($this->inputs as $target => $source) {
            if ($source instanceof ExpressionInterface) {
                $sub->setVariable($target, $source($context));
            } elseif ($execution->hasVariable($source)) {
                $sub->setVariable($target, $execution->getVariable($source));
            }
        }
        
        $execution->waitForSignal();
        
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
        
        $sub = $execution->getEngine()->findExecution($delegation['executionId']);
        
        if (!$sub instanceof VirtualExecution) {
            throw new \RuntimeException(\sprintf('Missing nested execution being signaled'));
        }
        
        $context = $execution->getEngine()->getExpressionContextFactory()->createContext($sub);
        
        foreach ($this->outputs as $target => $source) {
            if ($source instanceof ExpressionInterface) {
                $execution->setVariable($target, $source($context));
            } elseif ($sub->hasVariable($source)) {
                $execution->setVariable($target, $sub->getVariable($source));
            }
        }
        
        $execution->getEngine()->debug('Resuming {execution} at call activity "{task}"', [
            'execution' => (string) $execution,
            'task' => $this->getStringValue($this->name, $execution->getExpressionContext())
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
