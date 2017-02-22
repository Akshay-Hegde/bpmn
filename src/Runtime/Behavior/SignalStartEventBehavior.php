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
use KoolKode\BPMN\Runtime\Command\CreateSignalSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Similar to basic start event, signal subscriptions are handled by repository services.
 * 
 * @author Martin Schröder
 */
class SignalStartEventBehavior extends AbstractActivity implements StartEventBehaviorInterface
{
    protected $signal;

    protected $subProcessStart;

    protected $interrupting = true;

    public function __construct($signal, $subProcessStart = false)
    {
        $this->signal = (string) $signal;
        $this->subProcessStart = $subProcessStart ? true : false;
    }

    public function getSignalName()
    {
        return $this->signal;
    }

    public function isSubProcessStart()
    {
        return $this->subProcessStart;
    }

    public function isInterrupting()
    {
        return $this->interrupting;
    }

    public function setInterrupting($interrupting)
    {
        $this->interrupting = $interrupting ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, $signal, array $variables = [], array $delegation = [])
    {
        if ($signal !== $this->signal) {
            throw new \RuntimeException(sprintf('Start event awaits signal "%s", unable to process signal "%s"', $this->signal, $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        $this->leave($execution);
    }

    /**
     * {@inheritdoc}
     */
    public function createEventSubscriptions(VirtualExecution $execution, $activityId, Node $node = null)
    {
        $execution->getEngine()->executeCommand(new CreateSignalSubscriptionCommand($this->signal, $execution, $activityId, ($node === null) ? $execution->getNode() : $node));
    }
}
