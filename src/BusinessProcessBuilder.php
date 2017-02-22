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
use KoolKode\BPMN\Delegate\Behavior\ReceiveMessageTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\ReceiveTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\ScriptTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\TaskBehavior;
use KoolKode\BPMN\Runtime\Behavior\CallActivityBehavior;
use KoolKode\BPMN\Runtime\Behavior\EventBasedGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\EventSubProcessBehavior;
use KoolKode\BPMN\Runtime\Behavior\ExclusiveGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\InclusiveGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\ParallelGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateLinkCatchBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateLinkThrowBehavior;
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
use KoolKode\Expression\Parser\ExpressionLexer;
use KoolKode\Expression\Parser\ExpressionParser;
use KoolKode\Process\ExpressionTrigger;
use KoolKode\Process\ProcessBuilder;
use KoolKode\Process\ProcessModel;
use KoolKode\Process\Transition;
use KoolKode\Util\UUID;

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

    public function __construct($key, $title = '', ExpressionParser $parser = null)
    {
        $this->key = $key;
        $this->builder = new ProcessBuilder($title);
        
        if ($parser === null) {
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
        $model = $this->builder->build();
        
        $linkCatch = [];
        $linkThrow = [];
        
        foreach ($model->findNodes() as $node) {
            $behavior = $node->getBehavior();
            
            if ($behavior instanceof IntermediateLinkCatchBehavior) {
                $linkCatch[$behavior->getLink()] = $node->getId();
            }
            if ($behavior instanceof IntermediateLinkThrowBehavior) {
                $linkThrow[$node->getId()] = $behavior->getLink();
            }
        }
        
        if (!empty($linkCatch)) {
            foreach ($linkThrow as $nodeId => $link) {
                if (empty($linkCatch[$link])) {
                    throw new \RuntimeException(sprintf('No link catch event defined for link "%s" thrown by node %s', $link, $nodeId));
                }
                
                // This does the trick: insert a new transition directly connecting the link events.
                $trans = new Transition('link-' . UUID::createRandom(), $nodeId);
                $trans->to($linkCatch[$link]);
                
                $model->addItem($trans);
            }
        }
        
        return $model;
    }

    public function append(BusinessProcessBuilder $builder)
    {
        $this->builder->append($builder->builder);
        
        return $this;
    }

    public function startEvent($id, $subProcessStart = false, $name = null)
    {
        $behavior = new NoneStartEventBehavior($subProcessStart);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior)->initial();
        
        return $behavior;
    }

    public function messageStartEvent($id, $messageName, $subProcessStart = false, $name = null)
    {
        $behavior = new MessageStartEventBehavior($messageName, $subProcessStart);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function signalStartEvent($id, $signalName, $subProcessStart = false, $name = null)
    {
        $behavior = new SignalStartEventBehavior($signalName, $subProcessStart);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function endEvent($id, $name = null)
    {
        $behavior = new NoneEndEventBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function terminateEndEvent($id, $name = null)
    {
        $behavior = new TerminateEndEventBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function messageEndEvent($id, $name = null)
    {
        $behavior = new IntermediateMessageThrowBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function signalEndEvent($id, $signalName, $name = null)
    {
        $behavior = new IntermediateSignalThrowBehavior($signalName);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function sequenceFlow($id, $from, $to, $condition = null)
    {
        $transition = $this->builder->transition($id, $from, $to);
        
        if ($condition !== null) {
            $transition->trigger(new ExpressionTrigger($this->exp($condition)));
        }
        
        return $transition;
    }

    public function exclusiveGateway($id, $name = null)
    {
        $behavior = new ExclusiveGatewayBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function inclusiveGateway($id, $name = null)
    {
        $behavior = new InclusiveGatewayBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function parallelGateway($id, $name = null)
    {
        $behavior = new ParallelGatewayBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function eventBasedGateway($id, $name = null)
    {
        $behavior = new EventBasedGatewayBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function task($id, $name = null)
    {
        $behavior = new TaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function serviceTask($id, $name = null)
    {
        $behavior = new TaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function delegateTask($id, $typeName, $name = null)
    {
        $behavior = new DelegateTaskBehavior($id, $this->stringExp($typeName));
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function expressionTask($id, $expression, $name = null)
    {
        $behavior = new ExpressionTaskBehavior($id, $this->exp($expression));
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function receiveTask($id, $name = null)
    {
        $behavior = new ReceiveTaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function receiveMessageTask($id, $message, $name = null)
    {
        $behavior = new ReceiveMessageTaskBehavior($id, $message);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function userTask($id, $name = null)
    {
        $behavior = new UserTaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function manualTask($id, $name = null)
    {
        $behavior = new TaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function scriptTask($id, $language, $script, $name = null)
    {
        $behavior = new ScriptTaskBehavior($id);
        $behavior->setScript($script, $language);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function scriptResourceTask($id, $resource, $name = null)
    {
        $behavior = new ScriptTaskBehavior($id);
        $behavior->setScriptResource($resource);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function callActivity($id, $element, $name = null)
    {
        $behavior = new CallActivityBehavior($id, $element);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function subProcess($id, BusinessProcessBuilder $subProcess, $name = null)
    {
        $subModel = $subProcess->build();
        $startNode = $this->findSubProcessStartNode($id, $subModel);
        
        $behavior = new SubProcessBehavior($id, $startNode->getId());
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        $this->append($subProcess);
        
        return $behavior;
    }

    public function eventSubProcess($id, $attachedTo, BusinessProcessBuilder $subProcess, $name = null)
    {
        $subModel = $subProcess->build();
        $startNode = $this->findSubProcessStartNode($id, $subModel);
        
        $behavior = new EventSubProcessBehavior($id, $attachedTo, $startNode->getId());
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        $this->append($subProcess);
        
        foreach ($subModel->findStartNodes() as $startNode) {
            $sb = $startNode->getBehavior();
            
            if ($sb instanceof StartEventBehaviorInterface) {
                $behavior->setInterrupting($sb->isInterrupting());
            }
        }
        
        return $behavior;
    }

    protected function findSubProcessStartNode($id, ProcessModel $subModel)
    {
        $startNode = null;
        
        foreach ($subModel->findStartNodes() as $candidate) {
            $behavior = $candidate->getBehavior();
            
            if ($behavior instanceof StartEventBehaviorInterface) {
                $startNode = $candidate;
                
                break;
            }
        }
        
        if ($startNode === null) {
            throw new \RuntimeException(sprintf('Missing start node of sub process %s', $id));
        }
        
        return $startNode;
    }

    public function intermediateLinkCatchEvent($id, $link, $name = null)
    {
        $behavior = new IntermediateLinkCatchBehavior($link);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateLinkThrowEvent($id, $link, $name = null)
    {
        $behavior = new IntermediateLinkThrowBehavior($link);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateNoneEvent($id, $name = null)
    {
        $behavior = new IntermediateNoneEventBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateSignalCatchEvent($id, $signal, $name = null)
    {
        $behavior = new IntermediateSignalCatchBehavior($signal);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateMessageCatchEvent($id, $message, $name = null)
    {
        $behavior = new IntermediateMessageCatchBehavior($message);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateTimerDateEvent($id, $date, $name = null)
    {
        $behavior = new IntermediateTimerDateBehavior();
        $behavior->setDate($this->stringExp($date));
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateTimerDurationEvent($id, $duration, $name = null)
    {
        $behavior = new IntermediateTimerDurationBehavior();
        $behavior->setDuration($this->stringExp($duration));
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateSignalThrowEvent($id, $signal, $name = null)
    {
        $behavior = new IntermediateSignalThrowBehavior($signal);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateMessageThrowEvent($id, $name = null)
    {
        $behavior = new IntermediateMessageThrowBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function signalBoundaryEvent($id, $attachedTo, $signal, $name = null)
    {
        $behavior = new SignalBoundaryEventBehavior($id, $attachedTo, $signal);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function messageBoundaryEvent($id, $attachedTo, $message, $name = null)
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
        return ($input === null) ? null : $this->expressionParser->parse($this->normalize($input));
    }

    public function stringExp($input)
    {
        return ($input === null) ? null : $this->expressionParser->parseString($this->normalize($input));
    }
}
