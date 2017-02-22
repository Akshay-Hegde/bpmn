<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Delegate\Behavior;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Expression\ExpressionInterface;

/**
 * Implements service task behavior using an expression parsed from a BPMN process definition.
 * 
 * @author Martin Schröder
 */
class ExpressionTaskBehavior extends AbstractScopeActivity
{
    protected $expression;

    protected $resultVariable;

    public function __construct($activityId, ExpressionInterface $expression)
    {
        parent::__construct($activityId);
        
        $this->expression = $expression;
    }

    public function setResultVariable($var = null)
    {
        $this->resultVariable = ($var === null) ? null : (string) $var;
    }

    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution)
    {
        $engine = $execution->getEngine();
        $name = $this->getStringValue($this->name, $execution->getExpressionContext());
        
        $engine->debug('Execute expression in service task "{task}"', [
            'task' => $name
        ]);
        
        $result = $this->getValue($this->expression, $execution->getExpressionContext());
        
        if ($this->resultVariable !== null) {
            $execution->setVariable($this->resultVariable, $result);
        }
        
        $engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
        
        $this->leave($execution);
    }
}
