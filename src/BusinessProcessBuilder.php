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
use KoolKode\BPMN\Runtime\Behavior\ParallelGatewayBehavior;
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
use KoolKode\Process\Node;
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

    public function __construct(string $key, string $title = '', ?ExpressionParser $parser = null)
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

    public function getKey(): string
    {
        return $this->key;
    }

    public function build(): ProcessModel
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
                    throw new \RuntimeException(\sprintf('No link catch event defined for link "%s" thrown by node %s', $link, $nodeId));
                }
                
                // This does the trick: insert a new transition directly connecting the link events.
                $trans = new Transition('link-' . UUID::createRandom(), $nodeId);
                $trans->to($linkCatch[$link]);
                
                $model->addItem($trans);
            }
        }
        
        return $model;
    }

    public function append(BusinessProcessBuilder $builder): self
    {
        $this->builder->append($builder->builder);
        
        return $this;
    }

    public function startEvent(string $id, bool $subProcessStart = false, ?string $name = null): NoneStartEventBehavior
    {
        $behavior = new NoneStartEventBehavior($subProcessStart);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior)->initial();
        
        return $behavior;
    }

    public function messageStartEvent(string $id, string $messageName, bool $subProcessStart = false, ?string $name = null): MessageStartEventBehavior
    {
        $behavior = new MessageStartEventBehavior($messageName, $subProcessStart);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function signalStartEvent(string $id, string $signalName, bool $subProcessStart = false, ?string $name = null): SignalStartEventBehavior
    {
        $behavior = new SignalStartEventBehavior($signalName, $subProcessStart);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function endEvent(string $id, ?string $name = null): NoneEndEventBehavior
    {
        $behavior = new NoneEndEventBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function terminateEndEvent(string $id, ?string $name = null): TerminateEndEventBehavior
    {
        $behavior = new TerminateEndEventBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function messageEndEvent(string $id, ?string $name = null): IntermediateMessageThrowBehavior
    {
        $behavior = new IntermediateMessageThrowBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function signalEndEvent(string $id, string $signalName, ?string $name = null): IntermediateSignalThrowBehavior
    {
        $behavior = new IntermediateSignalThrowBehavior($signalName);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function sequenceFlow(string $id, string $from, string $to, ?string $condition = null): Transition
    {
        $transition = $this->builder->transition($id, $from, $to);
        
        if ($condition !== null) {
            $transition->trigger(new ExpressionTrigger($this->exp($condition)));
        }
        
        return $transition;
    }

    public function exclusiveGateway(string $id, ?string $name = null): ExclusiveGatewayBehavior
    {
        $behavior = new ExclusiveGatewayBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function inclusiveGateway(string $id, ?string $name = null): InclusiveGatewayBehavior
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

    public function eventBasedGateway(string $id, ?string $name = null): EventBasedGatewayBehavior
    {
        $behavior = new EventBasedGatewayBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function task(string $id, ?string $name = null): TaskBehavior
    {
        $behavior = new TaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function serviceTask(string $id, ?string $name = null): TaskBehavior
    {
        $behavior = new TaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function delegateTask(string $id, string $typeName, ?string $name = null): DelegateTaskBehavior
    {
        $behavior = new DelegateTaskBehavior($id, $this->stringExp($typeName));
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function expressionTask(string $id, string $expression, ?string $name = null): ExpressionTaskBehavior
    {
        $behavior = new ExpressionTaskBehavior($id, $this->exp($expression));
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function receiveTask(string $id, ?string $name = null): ReceiveTaskBehavior
    {
        $behavior = new ReceiveTaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function receiveMessageTask(string $id, string $message, ?string $name = null): ReceiveMessageTaskBehavior
    {
        $behavior = new ReceiveMessageTaskBehavior($id, $message);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function userTask(string $id, ?string $name = null): UserTaskBehavior
    {
        $behavior = new UserTaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function manualTask(string $id, ?string $name = null): TaskBehavior
    {
        $behavior = new TaskBehavior($id);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function scriptTask(string $id, string $language, string $script, ?string $name = null): ScriptTaskBehavior
    {
        $behavior = new ScriptTaskBehavior($id);
        $behavior->setScript($script, $language);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function scriptResourceTask(string $id, string $resource, ?string $name = null): ScriptTaskBehavior
    {
        $behavior = new ScriptTaskBehavior($id);
        $behavior->setScriptResource($resource);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function callActivity(string $id, string $processDefinitionKey, ?string $name = null): CallActivityBehavior
    {
        $behavior = new CallActivityBehavior($id, $processDefinitionKey);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function subProcess(string $id, BusinessProcessBuilder $subProcess, ?string $name = null): SubProcessBehavior
    {
        $subModel = $subProcess->build();
        $startNode = $this->findSubProcessStartNode($id, $subModel);
        
        $behavior = new SubProcessBehavior($id, $startNode->getId());
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        $this->append($subProcess);
        
        return $behavior;
    }

    public function eventSubProcess(string $id, string $attachedTo, BusinessProcessBuilder $subProcess, ?string $name = null): EventSubProcessBehavior
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

    protected function findSubProcessStartNode(string $id, ProcessModel $subModel): Node
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
            throw new \RuntimeException(\sprintf('Missing start node of sub process %s', $id));
        }
        
        return $startNode;
    }

    public function intermediateLinkCatchEvent(string $id, string $link, ?string $name = null): IntermediateLinkCatchBehavior
    {
        $behavior = new IntermediateLinkCatchBehavior($link);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateLinkThrowEvent(string $id, string $link, ?string $name = null): IntermediateLinkThrowBehavior
    {
        $behavior = new IntermediateLinkThrowBehavior($link);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateNoneEvent(string $id, ?string $name = null): IntermediateNoneEventBehavior
    {
        $behavior = new IntermediateNoneEventBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateSignalCatchEvent(string $id, string $signal, ?string $name = null): IntermediateSignalCatchBehavior
    {
        $behavior = new IntermediateSignalCatchBehavior($signal);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateMessageCatchEvent(string $id, string $message, ?string $name = null): IntermediateMessageCatchBehavior
    {
        $behavior = new IntermediateMessageCatchBehavior($message);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateTimerDateEvent(string $id, string $date, ?string $name = null): IntermediateTimerDateBehavior
    {
        $behavior = new IntermediateTimerDateBehavior();
        $behavior->setDate($this->stringExp($date));
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateTimerDurationEvent(string $id, string $duration, ?string $name = null): IntermediateTimerDurationBehavior
    {
        $behavior = new IntermediateTimerDurationBehavior();
        $behavior->setDuration($this->stringExp($duration));
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateSignalThrowEvent(string $id, string $signal, ?string $name = null): IntermediateSignalThrowBehavior
    {
        $behavior = new IntermediateSignalThrowBehavior($signal);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function intermediateMessageThrowEvent(string $id, ?string $name = null): IntermediateMessageThrowBehavior
    {
        $behavior = new IntermediateMessageThrowBehavior();
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function signalBoundaryEvent(string $id, string $attachedTo, string $signal, ?string $name = null): SignalBoundaryEventBehavior
    {
        $behavior = new SignalBoundaryEventBehavior($id, $attachedTo, $signal);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function messageBoundaryEvent(string $id, string $attachedTo, string $message, ?string $name = null): MessageBoundaryEventBehavior
    {
        $behavior = new MessageBoundaryEventBehavior($id, $attachedTo, $message);
        $behavior->setName($this->stringExp($name));
        
        $this->builder->node($id)->behavior($behavior);
        
        return $behavior;
    }

    public function normalize(?string $input): ?string
    {
        return ($input === null) ? null : \trim(\preg_replace("'\s+'", ' ', $input));
    }

    public function exp(?string $input): ?ExpressionInterface
    {
        return ($input === null) ? null : $this->expressionParser->parse($this->normalize($input));
    }

    public function stringExp(?string $input): ?ExpressionInterface
    {
        return ($input === null) ? null : $this->expressionParser->parseString($this->normalize($input));
    }
}
