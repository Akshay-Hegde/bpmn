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

use KoolKode\BPMN\Engine\AbstractActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\SignalEventReceivedCommand;

/**
 * Throws a signal event to all subscribed executions.
 * 
 * @author Martin Schröder
 */
class IntermediateSignalThrowBehavior extends AbstractActivity
{
    protected $signalName;

    public function __construct($signalName)
    {
        $this->signalName = (string) $signalName;
    }

    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution)
    {
        $execution->getEngine()->pushCommand(new SignalEventReceivedCommand($this->signalName, null, [], $execution));
        
        $execution->waitForSignal();
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
    {
        $this->passVariablesToExecution($execution, $variables);
        
        return $this->leave($execution);
    }
}
