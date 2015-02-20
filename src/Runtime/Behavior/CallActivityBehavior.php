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
	
	public function __construct($activityId, $processDefinitionKey)
	{
		parent::__construct($activityId);
		
		$this->processDefinitionKey = (string)$processDefinitionKey;
	}
	
	public function addInput($target, $source)
	{
		if($source instanceof ExpressionInterface)
		{
			$this->inputs[(string)$target] = $source;
		}
		else
		{
			$this->inputs[(string)$target] = (string)$source;
		}
	}
	
	public function addOutput($target, $source)
	{
		if($source instanceof ExpressionInterface)
		{
			$this->outputs[(string)$target] = $source;
		}
		else
		{
			$this->outputs[(string)$target] = (string)$source;
		}
	}
	
	public function enter(VirtualExecution $execution)
	{
		$context = $execution->getExpressionContext();
		$definition = $execution->getEngine()->getRepositoryService()->createProcessDefinitionQuery()->processDefinitionKey($this->processDefinitionKey)->findOne();
		
		$execution->getEngine()->debug('Starting process {process} from call activity "{task}"', [
			'process' => $this->processDefinitionKey,
			'task' => $this->getStringValue($this->name, $context)
		]);
		
		$start = $definition->getModel()->findInitialNodes();
		
		if(count($start) !== 1)
		{
			throw new \RuntimeException(sprintf('Missing single non start event in process %s', $definition->getKey()));
		}
		
		$sub = $execution->createNestedExecution($definition->getModel(), true, true);
		
		foreach($this->inputs as $target => $source)
		{
			if($source instanceof ExpressionInterface)
			{
				$sub->setVariable($target, $source($context));
			}
			elseif($execution->hasVariable($source))
			{
				$sub->setVariable($target, $execution->getVariable($source));
			}
		}
		
		$execution->waitForSignal();
		
		$sub->execute(array_shift($start));
	}
	
	public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
	{
		$sub = $execution->getEngine()->findExecution($delegation['executionId']);
		
		if(!$sub instanceof VirtualExecution)
		{
			throw new \RuntimeException(sprintf('Missing nested execution being signaled'));
		}
		
		$context = $execution->getEngine()->getExpressionContextFactory()->createContext($sub);
		
		foreach($this->outputs as $target => $source)
		{
			if($source instanceof ExpressionInterface)
			{
				$execution->setVariable($target, $source($context));
			}
			elseif($sub->hasVariable($source))
			{
				$execution->setVariable($target, $sub->getVariable($source));
			}
		}
		
		$execution->getEngine()->debug('Resuming {execution} at call activity "{task}"', [
			'execution' => (string)$execution,
			'task' => $this->getStringValue($this->name, $execution->getExpressionContext())
		]);
		
		return $this->leave($execution);
	}
	
	public function interrupt(VirtualExecution $execution, array $transitions = NULL)
	{
		foreach($execution->findChildExecutions() as $sub)
		{
			$sub->terminate(false);
		}
		
		parent::interrupt($execution, $transitions);
	}
}
