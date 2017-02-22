<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Delegate;

use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Test\BusinessProcessTestCase;

class TaskTest extends BusinessProcessTestCase
{
    protected $verifiedEvent = false;

    /**
     * Test event is being triggered for manual tasks.
     */
    public function test1()
    {
        $this->deployFile('Task1.bpmn');
        
        $this->eventDispatcher->connect(function (TaskExecutedEvent $event) {
            $this->assertEquals('test', $event->execution->getActivityId());
            $this->verifiedEvent = true;
        });
        
        $this->runtimeService->startProcessInstanceByKey('Task1');
        
        $this->assertTrue($this->verifiedEvent);
    }

    /**
     * Test event is being triggered for tasks.
     */
    public function test2()
    {
        $this->deployFile('Task2.bpmn');
        
        $this->eventDispatcher->connect(function (TaskExecutedEvent $event) {
            $this->assertEquals('test', $event->execution->getActivityId());
            $this->verifiedEvent = true;
        });
        
        $this->runtimeService->startProcessInstanceByKey('Task2');
        
        $this->assertTrue($this->verifiedEvent);
    }
}
