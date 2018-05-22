<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Test;

use KoolKode\BPMN\Delegate\DelegateExecutionInterface;
use KoolKode\BPMN\Delegate\DelegateTaskInterface;

/**
 * Registers a method as BPMN 2.0 service task handler.
 * 
 * @author Martin Schröder
 */
class ServiceTaskHandler implements DelegateTaskInterface
{
    protected $serviceTask;

    protected $processKey;
    
    protected $callback;

    public function __construct(string $serviceTask, ?string $processKey, callable $callback)
    {
        $this->serviceTask = $serviceTask;
        $this->processKey = $processKey;
        $this->callback = $callback;
    }

    public function getServiceTask(): string
    {
        return $this->serviceTask;
    }

    public function getProcessKey(): ?string
    {
        return $this->processKey;
    }
    
    public function execute(DelegateExecutionInterface $execution): void
    {
        ($this->callback)($execution);
    }
}
