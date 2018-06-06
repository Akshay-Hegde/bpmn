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

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;

/**
 * Notifies event listeners when a message throw event has been executed.
 * 
 * This command has lower priority leaving some time for concurrent executions to create subscriptions etc.
 * 
 * @author Martin Schröder
 */
class ThrowMessageCommand extends AbstractBusinessCommand
{
    protected $executionId;

    public function __construct(VirtualExecution $execution)
    {
        $this->executionId = $execution->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function isSerializable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        return self::PRIORITY_DEFAULT - 500;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ProcessEngine $engine): void
    {
        $execution = $engine->findExecution($this->executionId);
        
        $engine->notify(new MessageThrownEvent(new DelegateExecution($execution), $engine));
        
        $execution->signal();
    }
}
