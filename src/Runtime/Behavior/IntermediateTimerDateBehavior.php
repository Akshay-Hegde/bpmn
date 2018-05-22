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
use KoolKode\BPMN\Runtime\Command\CreateTimerSubscriptionCommand;
use KoolKode\Expression\ExpressionInterface;
use KoolKode\Process\Node;

/**
 * @author Martin Schröder
 */
class IntermediateTimerDateBehavior extends AbstractActivity implements IntermediateCatchEventInterface
{
    protected $date;

    public function setDate(ExpressionInterface $date)
    {
        $this->date = $date;
    }

    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $execution->waitForSignal();
    }

    /**
     * {@inheritdoc}
     */
    public function processSignal(VirtualExecution $execution, ?string $signal, array $variables = [], array $delegation = []): void
    {
        if ($signal !== 'timer') {
            throw new \RuntimeException(sprintf('Timer catch event cannot process signal "%s"', $signal));
        }
        
        $this->passVariablesToExecution($execution, $variables);
        
        $this->leave($execution);
    }

    /**
     * {@inheritdoc}
     */
    public function createEventSubscriptions(VirtualExecution $execution, string $activityId, ?Node $node = null): void
    {
        $date = $this->getDateValue($this->date, $execution->getExpressionContext());
        
        if (!$date instanceof \DateTimeImmutable) {
            throw new \RuntimeException(sprintf('Expecting DateTimeInterface, given %s', is_object($date) ? get_class($date) : gettype($date)));
        }
        
        $execution->getEngine()->executeCommand(new CreateTimerSubscriptionCommand($date, $execution, $activityId, ($node === null) ? $execution->getNode() : $node));
    }
}
