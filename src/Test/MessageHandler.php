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

namespace KoolKode\BPMN\Test;

use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;

/**
 * Registers a method as BPMN 2.0 message handler.
 * 
 * @author Martin Schröder
 */
class MessageHandler
{
    protected $messageName;

    protected $processKey;
    
    protected $callback;

    public function __construct(string $messageName, ?string $processKey, callable $callback)
    {
        $this->messageName = $messageName;
        $this->processKey = $processKey;
        $this->callback = $callback;
    }

    public function getMessageName(): string
    {
        return $this->messageName;
    }

    public function getProcessKey(): ?string
    {
        return $this->processKey;
    }

    public function execute(MessageThrownEvent $event): void
    {
        ($this->callback)($event);
    }
}
