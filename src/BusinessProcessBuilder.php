<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN;

use KoolKode\BPMN\Delegate\Behavior\DelegateTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\ExpressionTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\ReceiveTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\ScriptTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\TaskBehavior;
use KoolKode\BPMN\Runtime\Behavior\CallActivityBehavior;
use KoolKode\BPMN\Runtime\Behavior\EventBasedGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\EventSubProcessBehavior;
use KoolKode\BPMN\Runtime\Behavior\ExclusiveGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\InclusiveGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\ParallelGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateMessageCatchBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateMessageThrowBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateNoneEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateSignalCatchBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateSignalThrowBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateTimerDateBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateTimerDurationBehavior;
use KoolKode\BPMN\Runtime\Behavior\MessageBoundaryEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\MessageStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\NoneEndEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\NoneStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\SignalBoundaryEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\SignalStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\StartEventBehaviorInterface;
use KoolKode\BPMN\Runtime\Behavior\SubProcessBehavior;
use KoolKode\BPMN\Runtime\Behavior\TerminateEndEventBehavior;
use KoolKode\BPMN\Task\Behavior\UserTaskBehavior;
use KoolKode\Expression\ExpressionInterface;
use KoolKode\Expression\Parser\ExpressionLexer;
use KoolKode\Expression\Parser\ExpressionParser;
use KoolKode\Process\ExpressionTrigger;
use KoolKode\Process\ProcessBuilder;
use KoolKode\Process\ProcessModel;

/**
 * Convenient builder that aids during creation of BPMN 2.0 process models.
 * 
 * @author Martin Schröder
 */
class BusinessProcessBuilder
{
	protected $key;
	
	protected $builder;
	
	protected $expressionParser;
	
	public function __construct($key, $title = '', ExpressionParser $parser = NULL)
	{
		$this->key = $key;
		$this->builder = new ProcessBuilder($title);
		
		if($parser === NULL)
		{
			$lexer = new ExpressionLexer();
			$lexer->setDelimiters('#{', '}');
		
			$parser = new ExpressionParser($lexer);
		}
		
		$this->expressionParser = $parser;
	}
	
	public function getKey()
	{
		return $this->key;
	}
	
	public function build()
	{
		return $this->builder->build();
	}
	
	public function append(BusinessProcessBuilder $builder)
	{
		$this->builder->append($builder->builder);
		
		return $this;
	}
	
	public function startEvent($id, $subProcessStart = false, $name = NULL)
	{
		$behavior = new NoneStartEventBehavior($subProcessStart);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior)->initial();
		
		return $behavior;
	}
	
	public function messageStartEvent($id, $messageName, $subProcessStart = false, $name = NULL)
	{
		$behavior = new MessageStartEventBehavior($messageName, $subProcessStart);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function signalStartEvent($id, $signalName, $subProcessStart = false, $name = NULL)
	{
		$behavior = new SignalStartEventBehavior($signalName, $subProcessStart);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function endEvent($id, $name = NULL)
	{
		$behavior = new NoneEndEventBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function terminateEndEvent($id, $name = NULL)
	{
		$behavior = new TerminateEndEventBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function messageEndEvent($id, $name = NULL)
	{
		$behavior = new IntermediateMessageThrowBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function signalEndEvent($id, $signalName, $name = NULL)
	{
		$behavior = new IntermediateSignalThrowBehavior($signalName);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function sequenceFlow($id, $from, $to, $condition = NULL)
	{
		$transition = $this->builder->transition($id, $from, $to);
		
		if($condition !== NULL)
		{
			$transition->trigger(new ExpressionTrigger($this->exp($condition)));
		}
		
		return $transition;
	}
	
	public function exclusiveGateway($id, $name = NULL)
	{
		$behavior = new ExclusiveGatewayBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function inclusiveGateway($id, $name = NULL)
	{
		$behavior = new InclusiveGatewayBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function parallelGateway($id, $name = NULL)
	{
		$behavior = new ParallelGatewayBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function eventBasedGateway($id, $name = NULL)
	{
		$behavior = new EventBasedGatewayBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function task($id, $name = NULL)
	{
		$behavior = new TaskBehavior($id);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function serviceTask($id, $name = NULL)
	{
		$behavior = new TaskBehavior($id);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function delegateTask($id, $typeName, $name = NULL)
	{
		$behavior = new DelegateTaskBehavior($id, $this->stringExp($typeName));
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function expressionTask($id, $expression, $name = NULL)
	{
		$behavior = new ExpressionTaskBehavior($id, $this->exp($expression));
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function receiveTask($id, $name = NULL)
	{
		$behavior = new ReceiveTaskBehavior($id);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function userTask($id, $name = NULL)
	{
		$behavior = new UserTaskBehavior($id);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function manualTask($id, $name = NULL)
	{
		$behavior = new TaskBehavior($id);
		$behavior->setName($this->stringExp($name));
	
		$this->builder->node($id)->behavior($behavior);
	
		return $behavior;
	}
	
	public function scriptTask($id, $language, $script, $name = NULL)
	{
		$behavior = new ScriptTaskBehavior($id);
		$behavior->setScript($script, $language);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function scriptResourceTask($id, $resource, $name = NULL)
	{
		$behavior = new ScriptTaskBehavior($id);
		$behavior->setScriptResource($resource);
		$behavior->setName($this->stringExp($name));
	
		$this->builder->node($id)->behavior($behavior);
	
		return $behavior;
	}
	
	public function callActivity($id, $element, $name = NULL)
	{
		$behavior = new CallActivityBehavior($id, $element);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function subProcess($id, BusinessProcessBuilder $subProcess, $name = NULL)
	{
		$subModel = $subProcess->build();
		$startNode = $this->findSubProcessStartNode($id, $subModel);
		
		$behavior = new SubProcessBehavior($id, $startNode->getId());
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		$this->append($subProcess);
		
		return $behavior;
	}
	
	public function eventSubProcess($id, $attachedTo, BusinessProcessBuilder $subProcess, $name = NULL)
	{
		$subModel = $subProcess->build();
		$startNode = $this->findSubProcessStartNode($id, $subModel);
		
		$behavior = new EventSubProcessBehavior($id, $attachedTo, $startNode->getId());
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		$this->append($subProcess);
		
		foreach($subModel->findStartNodes() as $startNode)
		{
			$sb = $startNode->getBehavior();
		
			if($sb instanceof StartEventBehaviorInterface)
			{
				$behavior->setInterrupting($sb->isInterrupting());
			}
		}
		
		return $behavior;
	}
	
	protected function findSubProcessStartNode($id, ProcessModel $subModel)
	{
		$startNode = NULL;
		
		foreach($subModel->findStartNodes() as $candidate)
		{
			$behavior = $candidate->getBehavior();
		
			if($behavior instanceof StartEventBehaviorInterface)
			{
				$startNode = $candidate;
					
				break;
			}
		}
			
		if($startNode === NULL)
		{
			throw new \RuntimeException(sprintf('Missing start node of sub process %s', $id));
		}
		
		return $startNode;
	}
	
	public function intermediateNoneEvent($id, $name = NULL)
	{
		$behavior = new IntermediateNoneEventBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function intermediateSignalCatchEvent($id, $signal, $name = NULL)
	{
		$behavior = new IntermediateSignalCatchBehavior($signal);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function intermediateMessageCatchEvent($id, $message, $name = NULL)
	{
		$behavior = new IntermediateMessageCatchBehavior($message);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function intermediateTimerDateEvent($id, $date, $name = NULL)
	{
		$behavior = new IntermediateTimerDateBehavior();
		$behavior->setDate($this->stringExp($date));
		$behavior->setName($this->stringExp($name));
	
		$this->builder->node($id)->behavior($behavior);
	
		return $behavior;
	}
	
	public function intermediateTimerDurationEvent($id, $duration, $name = NULL)
	{
		$behavior = new IntermediateTimerDurationBehavior();
		$behavior->setDuration($this->stringExp($duration));
		$behavior->setName($this->stringExp($name));
	
		$this->builder->node($id)->behavior($behavior);
	
		return $behavior;
	}
	
	public function intermediateSignalThrowEvent($id, $signal, $name = NULL)
	{
		$behavior = new IntermediateSignalThrowBehavior($signal);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function intermediateMessageThrowEvent($id, $name = NULL)
	{
		$behavior = new IntermediateMessageThrowBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function signalBoundaryEvent($id, $attachedTo, $signal, $name = NULL)
	{
		$behavior = new SignalBoundaryEventBehavior($id, $attachedTo, $signal);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function messageBoundaryEvent($id, $attachedTo, $message, $name = NULL)
	{
		$behavior = new MessageBoundaryEventBehavior($id, $attachedTo, $message);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function normalize($input)
	{
		return trim(preg_replace("'\s+'", ' ', $input));
	}
	
	public function exp($input)
	{
		return ($input === NULL) ? NULL : $this->expressionParser->parse($this->normalize($input));
	}
	
	public function stringExp($input)
	{
		return ($input === NULL) ? NULL : $this->expressionParser->parseString($this->normalize($input));
	}
}
