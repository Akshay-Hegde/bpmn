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

use KoolKode\Xml\XmlDocumentBuilder;

/**
 * Reads process models from BPMN 2.0 process and collaboration diagram files.
 * 
 * @author Martin Schröder
 */
class DiagramLoader
{
    const NS_MODEL = 'http://www.omg.org/spec/BPMN/20100524/MODEL';

    const NS_DI = 'http://www.omg.org/spec/BPMN/20100524/DI';

    const NS_DC = 'http://www.omg.org/spec/DD/20100524/DC';

    const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    const NS_IMPL = 'http://activiti.org/bpmn';

    protected $xpath;

    protected $signals = [];

    protected $messages = [];

    protected $subProcessId;

    public function parseDiagramFile(string $file): array
    {
        return $this->parseDiagram((new XmlDocumentBuilder())->buildDocument(new \SplFileInfo($file)));
    }

    public function parseDiagramString(string $contents): array
    {
        return $this->parseDiagram((new XmlDocumentBuilder())->buildFromSource($contents));
    }

    public function parseDiagram(\DOMDocument $xml): array
    {
        try {
            $this->xpath = $this->createXPath($xml);
            
            foreach ($this->xpath->query('/m:definitions/m:message[@id][@name]') as $messageElement) {
                $this->messages[\trim($messageElement->getAttribute('id'))] = \trim($messageElement->getAttribute('name'));
            }
            
            foreach ($this->xpath->query('/m:definitions/m:signal[@id][@name]') as $signalElement) {
                $this->signals[\trim($signalElement->getAttribute('id'))] = \trim($signalElement->getAttribute('name'));
            }
            
            $result = [];
            
            foreach ($this->xpath->query('/m:definitions/m:process[@id]') as $processElement) {
                if ('true' === \strtolower($processElement->getAttribute('isExecutable'))) {
                    $result[] = $this->parseProcessDefinition($processElement);
                }
            }
            
            if (empty($result)) {
                throw new \OutOfBoundsException('No executable process definitions found');
            }
        } finally {
            $this->xpath = null;
            $this->signals = [];
            $this->messages = [];
            $this->subProcessId = null;
        }
        
        return $result;
    }

    protected function parseProcessDefinition(\DOMElement $process): BusinessProcessBuilder
    {
        $title = $process->hasAttribute('name') ? \trim($process->getAttribute('name')) : '';
        $builder = new BusinessProcessBuilder($process->getAttribute('id'), $title);
        
        foreach ($this->xpath->query('m:*[@id]', $process) as $element) {
            $this->parseElement($element, $builder);
        }
        
        return $builder;
    }

    protected function parseElement(\DOMElement $el, BusinessProcessBuilder $builder)
    {
        $id = $el->getAttribute('id');
        
        switch ($el->localName) {
            case 'sequenceFlow':
                return $this->parseSequenceFlow($id, $el, $builder);
            case 'serviceTask':
                return $this->parseServiceTask($id, $el, $builder);
            case 'scriptTask':
                return $this->parseScriptTask($id, $el, $builder);
            case 'userTask':
                return $this->parseUserTask($id, $el, $builder);
            case 'task':
                return $this->parseManualTask($id, $el, $builder);
            case 'manualTask':
                return $this->parseManualTask($id, $el, $builder);
            case 'receiveTask':
                return $this->parseReceiveTask($id, $el, $builder);
            case 'sendTask':
                return $this->parseSendTask($id, $el, $builder);
            case 'callActivity':
                return $this->parseCallActivity($id, $el, $builder);
            case 'subProcess':
                return $this->parseSubProcess($id, $el, $builder);
            case 'boundaryEvent':
                return $this->parseBoundaryEvent($id, $el, $builder);
            case 'startEvent':
                $event = $this->parseStartEvent($id, $el, $builder);
                $event->setAsyncBefore($this->getAsyncBefore($el));
                $event->setAsyncAfter($this->getAsyncAfter($el));
                
                return $event;
            case 'endEvent':
                $event = $this->parseEndEvent($id, $el, $builder);
                $event->setAsyncBefore($this->getAsyncBefore($el));
                $event->setAsyncAfter($this->getAsyncAfter($el));
                
                return $event;
            case 'intermediateCatchEvent':
                $event = $this->parseIntermediateCatchEvent($id, $el, $builder);
                $event->setAsyncBefore($this->getAsyncBefore($el));
                $event->setAsyncAfter($this->getAsyncAfter($el));
                
                return $event;
            case 'intermediateThrowEvent':
                $event = $this->parseIntermediateThrowEvent($id, $el, $builder);
                $event->setAsyncBefore($this->getAsyncBefore($el));
                $event->setAsyncAfter($this->getAsyncAfter($el));
                
                return $event;
            case 'exclusiveGateway':
                return $this->parseExclusiveGateway($id, $el, $builder);
            case 'inclusiveGateway':
                return $this->parseInclusiveGateway($id, $el, $builder);
            case 'parallelGateway':
                return $this->parseParallelGateway($id, $el, $builder);
            case 'eventBasedGateway':
                return $this->parseEventBasedGateway($id, $el, $builder);
        }
    }

    protected function parseSequenceFlow(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $condition = null;
        
        foreach ($this->xpath->query('m:conditionExpression', $el) as $conditionElement) {
            $type = (string) $conditionElement->getAttributeNS(self::NS_XSI, 'type');
            $type = \explode(':', $type, 2);
            
            if (\count($type) == 2) {
                $uri = $conditionElement->lookupNamespaceURI($type[0]);
                
                if ($uri == self::NS_MODEL && $type[1] == 'tFormalExpression') {
                    $condition = \trim($conditionElement->textContent);
                }
            }
        }
        
        return $builder->sequenceFlow($id, $el->getAttribute('sourceRef'), $el->getAttribute('targetRef'), $condition);
    }

    protected function parseServiceTask(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        if ($el->hasAttributeNS(self::NS_IMPL, 'class') && '' !== \trim($el->getAttributeNS(self::NS_IMPL, 'class'))) {
            $delegateTask = $builder->delegateTask($id, $el->getAttributeNS(self::NS_IMPL, 'class'), $el->getAttribute('name'));
            $delegateTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
            $delegateTask->setAsyncBefore($this->getAsyncBefore($el));
            $delegateTask->setAsyncAfter($this->getAsyncAfter($el));
            
            return $delegateTask;
        }
        
        if ($el->hasAttributeNS(self::NS_IMPL, 'expression') && '' !== $el->getAttributeNS(self::NS_IMPL, 'expression')) {
            $expressionTask = $builder->expressionTask($id, $el->getAttributeNS(self::NS_IMPL, 'expression'), $el->getAttribute('name'));
            $expressionTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
            $expressionTask->setAsyncBefore($this->getAsyncBefore($el));
            $expressionTask->setAsyncAfter($this->getAsyncAfter($el));
            
            if ($el->hasAttributeNS(self::NS_IMPL, 'resultVariable')) {
                $expressionTask->setResultVariable($el->getAttributeNS(self::NS_IMPL, 'resultVariable'));
            }
            
            return $expressionTask;
        }
        
        $serviceTask = $builder->serviceTask($id, $el->getAttribute('name'));
        $serviceTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
        $serviceTask->setAsyncBefore($this->getAsyncBefore($el));
        $serviceTask->setAsyncAfter($this->getAsyncAfter($el));
        
        return $serviceTask;
    }

    protected function parseScriptTask(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        if ($el->hasAttributeNS(self::NS_IMPL, 'resource')) {
            $scriptTask = $builder->scriptResourceTask($id, $el->getAttributeNS(self::NS_IMPL, 'resource'), $el->getAttribute('name'));
        } else {
            $script = '';
            
            foreach ($this->xpath->query('m:script', $el) as $scriptElement) {
                $script .= $scriptElement->textContent;
            }
            
            $scriptTask = $builder->scriptTask($id, $el->getAttribute('scriptFormat'), $script, $el->getAttribute('name'));
        }
        
        $scriptTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
        $scriptTask->setAsyncBefore($this->getAsyncBefore($el));
        $scriptTask->setAsyncAfter($this->getAsyncAfter($el));
        
        if ($el->hasAttributeNS(self::NS_IMPL, 'resultVariable')) {
            $scriptTask->setResultVariable($el->getAttributeNS(self::NS_IMPL, 'resultVariable'));
        }
        
        return $scriptTask;
    }

    protected function parseUserTask(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $userTask = $builder->userTask($id, $el->getAttribute('name'));
        $userTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
        $userTask->setAsyncBefore($this->getAsyncBefore($el));
        $userTask->setAsyncAfter($this->getAsyncAfter($el));
        
        if ($el->hasAttributeNS(self::NS_IMPL, 'assignee') && '' !== \trim($el->getAttributeNS(self::NS_IMPL, 'assignee'))) {
            $userTask->setAssignee($builder->stringExp($el->getAttributeNS(self::NS_IMPL, 'assignee')));
        }
        
        if ($el->hasAttributeNS(self::NS_IMPL, 'priority') && '' !== \trim($el->getAttributeNS(self::NS_IMPL, 'priority'))) {
            $userTask->setPriority($builder->stringExp($el->getAttributeNS(self::NS_IMPL, 'priority')));
        }
        
        if ($el->hasAttributeNS(self::NS_IMPL, 'dueDate') && '' !== \trim($el->getAttributeNS(self::NS_IMPL, 'dueDate'))) {
            $userTask->setDueDate($builder->exp($el->getAttributeNS(self::NS_IMPL, 'dueDate')));
        }
        
        return $userTask;
    }

    protected function parseManualTask(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $manualTask = $builder->manualTask($id, $el->getAttribute('name'));
        $manualTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
        $manualTask->setAsyncBefore($this->getAsyncBefore($el));
        $manualTask->setAsyncAfter($this->getAsyncAfter($el));
        
        return $manualTask;
    }

    protected function parseReceiveTask(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $receiveTask = null;
        
        if ($el->hasAttribute('messageRef')) {
            $message = $this->messages[$el->getAttribute('messageRef')];
            
            $receiveTask = $builder->receiveMessageTask($id, $message, $el->getAttribute('name'));
        } else {
            $receiveTask = $builder->receiveTask($id, $el->getAttribute('name'));
        }
        
        $receiveTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
        $receiveTask->setAsyncBefore($this->getAsyncBefore($el));
        $receiveTask->setAsyncAfter($this->getAsyncAfter($el));
        
        return $receiveTask;
    }

    protected function parseSendTask(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        return $builder->intermediateMessageThrowEvent($id, $el->getAttribute('name'));
    }

    protected function parseCallActivity(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $call = $builder->callActivity($id, $el->getAttribute('calledElement'), $el->getAttribute('name'));
        $call->setDocumentation($builder->stringExp($this->getDocumentation($el)));
        $call->setAsyncBefore($this->getAsyncBefore($el));
        $call->setAsyncAfter($this->getAsyncAfter($el));
        
        foreach ($this->xpath->query('m:extensionElements/i:in[@source]', $el) as $in) {
            $call->addInput($in->getAttribute('target'), $in->getAttribute('source'));
        }
        
        foreach ($this->xpath->query('m:extensionElements/i:in[@sourceExpression]', $el) as $in) {
            $call->addInput($in->getAttribute('target'), $builder->exp($in->getAttribute('sourceExpression')));
        }
        
        foreach ($this->xpath->query('m:extensionElements/i:out[@source]', $el) as $out) {
            $call->addOutput($out->getAttribute('target'), $out->getAttribute('source'));
        }
        
        foreach ($this->xpath->query('m:extensionElements/i:out[@sourceExpression]', $el) as $out) {
            $call->addOutput($out->getAttribute('target'), $builder->exp($out->getAttribute('sourceExpression')));
        }
        
        return $call;
    }

    protected function parseSubProcess(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $parentSubId = $this->subProcessId;
        $this->subProcessId = $id;
        
        try {
            $triggeredByEvent = ('true' === \strtolower($el->getAttribute('triggeredByEvent')));
            $inner = $this->parseProcessDefinition($el);
            
            if ($triggeredByEvent) {
                $sub = $builder->eventSubProcess($id, $parentSubId, $inner, $el->getAttribute('name'));
            } else {
                $sub = $builder->subProcess($id, $inner, $el->getAttribute('name'));
            }
            
            $sub->setAsyncBefore($this->getAsyncBefore($el));
            $sub->setAsyncAfter($this->getAsyncAfter($el));
            
            return $sub;
        } finally {
            $this->subProcessId = $parentSubId;
        }
    }

    protected function parseStartEvent(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        foreach ($this->xpath->query('m:messageEventDefinition', $el) as $messageElement) {
            $message = $this->messages[$messageElement->getAttribute('messageRef')];
            
            $messageStart = $builder->messageStartEvent($id, $message, $this->subProcessId !== null, $el->getAttribute('name'));
            $messageStart->setInterrupting('false' !== \strtolower($el->getAttribute('isInterrupting')));
            
            return $messageStart;
        }
        
        foreach ($this->xpath->query('m:signalEventDefinition', $el) as $signalElement) {
            $signal = $this->signals[$signalElement->getAttribute('signalRef')];
            
            $signalStart = $builder->signalStartEvent($id, $signal, $this->subProcessId !== null, $el->getAttribute('name'));
            $signalStart->setInterrupting('false' != \strtolower($el->getAttribute('isInterrupting')));
            
            return $signalStart;
        }
        
        return $builder->startEvent($id, $this->subProcessId !== null, $el->getAttribute('name'));
    }

    protected function parseEndEvent(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        foreach ($this->xpath->query('m:terminateEventDefinition', $el) as $def) {
            return $builder->terminateEndEvent($id, $el->getAttribute('name'));
        }
        
        foreach ($this->xpath->query('m:messageEventDefinition', $el) as $def) {
            return $builder->messageEndEvent($id, $el->getAttribute('name'));
        }
        
        foreach ($this->xpath->query('m:signalEventDefinition', $el) as $def) {
            $signal = $this->signals[$def->getAttribute('signalRef')];
            
            return $builder->signalEndEvent($id, $signal, $el->getAttribute('name'));
        }
        
        return $builder->endEvent($id, $el->getAttribute('name'));
    }

    protected function parseIntermediateCatchEvent(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        foreach ($this->xpath->query('m:messageEventDefinition', $el) as $messageElement) {
            $message = $this->messages[$messageElement->getAttribute('messageRef')];
            
            return $builder->intermediateMessageCatchEvent($id, $message, $el->getAttribute('name'));
        }
        
        foreach ($this->xpath->query('m:signalEventDefinition', $el) as $signalElement) {
            $signal = $this->signals[$signalElement->getAttribute('signalRef')];
            
            return $builder->intermediateSignalCatchEvent($id, $signal, $el->getAttribute('name'));
        }
        
        foreach ($this->xpath->query('m:timerEventDefinition', $el) as $timerElement) {
            foreach ($this->xpath->query('m:timeDate', $timerElement) as $dateElement) {
                $date = \trim($dateElement->textContent);
                
                return $builder->intermediateTimerDateEvent($id, $date, $el->getAttribute('name'));
            }
            
            foreach ($this->xpath->query('m:timeDuration', $timerElement) as $durationElement) {
                $duration = \trim($durationElement->textContent);
                
                return $builder->intermediateTimerDurationEvent($id, $duration, $el->getAttribute('name'));
            }
        }
        
        foreach ($this->xpath->query('m:linkEventDefinition', $el) as $def) {
            $link = $def->getAttribute('name');
            
            return $builder->intermediateLinkCatchEvent($id, $link, $el->getAttribute('name'));
        }
        
        return $builder->intermediateNoneEvent($id, $el->getAttribute('name'));
    }

    protected function parseIntermediateThrowEvent(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        foreach ($this->xpath->query('m:messageEventDefinition', $el) as $def) {
            return $builder->intermediateMessageThrowEvent($id, $el->getAttribute('name'));
        }
        
        foreach ($this->xpath->query('m:signalEventDefinition', $el) as $def) {
            $signal = $this->signals[$def->getAttribute('signalRef')];
            
            return $builder->intermediateSignalThrowEvent($id, $signal, $el->getAttribute('name'));
        }
        
        foreach ($this->xpath->query('m:linkEventDefinition', $el) as $def) {
            $link = $def->getAttribute('name');
            
            return $builder->intermediateLinkThrowEvent($id, $link, $el->getAttribute('name'));
        }
        
        return $builder->intermediateNoneEvent($id, $el->getAttribute('name'));
    }

    protected function parseBoundaryEvent(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $attachedTo = $el->getAttribute('attachedToRef');
        $cancelActivity = true;
        
        if ($el->hasAttribute('cancelActivity')) {
            $cancelActivity = (\strtolower($el->getAttribute('cancelActivity')) == 'true');
        }
        
        foreach ($this->xpath->query('m:messageEventDefinition', $el) as $messageElement) {
            $message = $this->messages[$messageElement->getAttribute('messageRef')];
            
            $event = $builder->messageBoundaryEvent($id, $attachedTo, $message, $el->getAttribute('name'));
            $event->setInterrupting($cancelActivity);
            
            return $event;
        }
        
        foreach ($this->xpath->query('m:signalEventDefinition', $el) as $def) {
            $signal = $this->signals[$def->getAttribute('signalRef')];
            
            $event = $builder->signalBoundaryEvent($id, $attachedTo, $signal, $el->getAttribute('name'));
            $event->setInterrupting($cancelActivity);
            
            return $event;
        }
        
        throw new \RuntimeException('Unsupported boundary event type with id ' . $id);
    }

    protected function parseExclusiveGateway(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $gateway = $builder->exclusiveGateway($id, $el->getAttribute('name'));
        $gateway->setDefaultFlow($el->getAttribute('default'));
        $gateway->setAsyncBefore($this->getAsyncBefore($el));
        $gateway->setAsyncAfter($this->getAsyncAfter($el));
        
        return $gateway;
    }

    protected function parseInclusiveGateway(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $gateway = $builder->inclusiveGateway($id, $el->getAttribute('name'));
        $gateway->setDefaultFlow($el->getAttribute('default'));
        $gateway->setAsyncBefore($this->getAsyncBefore($el));
        $gateway->setAsyncAfter($this->getAsyncAfter($el));
        
        return $gateway;
    }

    protected function parseParallelGateway(string $id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $gateway = $builder->parallelGateway($id, $el->getAttribute('name'));
        $gateway->setAsyncBefore($this->getAsyncBefore($el));
        $gateway->setAsyncAfter($this->getAsyncAfter($el));
        
        return $gateway;
    }

    protected function parseEventBasedGateway($id, \DOMElement $el, BusinessProcessBuilder $builder)
    {
        $gateway = $builder->eventBasedGateway($id, $el->getAttribute('name'));
        $gateway->setAsyncBefore($this->getAsyncBefore($el));
        
        return $gateway;
    }

    protected function getDocumentation(\DOMElement $el): ?string
    {
        $docs = [];
        
        foreach ($this->xpath->query('m:documentation', $el) as $doc) {
            $docs[] = $doc->textContent;
        }
        
        return empty($docs) ? null : \implode(' ', $docs);
    }

    protected function getAsyncBefore(\DOMElement $el): bool
    {
        if (\strtolower($el->getAttributeNS(self::NS_IMPL, 'asyncBefore')) == 'true') {
            return true;
        }
        
        return \strtolower($el->getAttributeNS(self::NS_IMPL, 'async')) == 'true';
    }

    protected function getAsyncAfter(\DOMElement $el): bool
    {
        return \strtolower($el->getAttributeNS(self::NS_IMPL, 'asyncAfter')) == 'true';
    }

    protected function createXPath(\DOMNode $xml): \DOMXPath
    {
        $xpath = new \DOMXPath(($xml instanceof \DOMDocument) ? $xml : $xml->ownerDocument);
        $xpath->registerNamespace('m', self::NS_MODEL);
        $xpath->registerNamespace('di', self::NS_DI);
        $xpath->registerNamespace('dc', self::NS_DC);
        $xpath->registerNamespace('xsi', self::NS_XSI);
        $xpath->registerNamespace('i', self::NS_IMPL);
        
        return $xpath;
    }
}
