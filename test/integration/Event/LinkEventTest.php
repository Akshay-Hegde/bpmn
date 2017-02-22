<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Gateway;

use KoolKode\BPMN\Test\BusinessProcessTestCase;

class LinkEventTest extends BusinessProcessTestCase
{
    public function test1()
    {
        $this->deployFile('LinkEvent1.bpmn');
        
        $this->runtimeService->startProcessInstanceByKey('LinkEvent1');
        
        $tasks = $this->taskService->createTaskQuery()->orderByTaskDefinitionKey()->findAll();
        $this->assertCount(2, $tasks);
        $this->assertEquals('task1', $tasks[0]->getDefinitionKey());
        $this->assertEquals('task2', $tasks[1]->getDefinitionKey());
        
        $this->taskService->complete($tasks[0]->getId());
        $this->taskService->complete($tasks[1]->getId());
        
        $this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
    }

    public function test2()
    {
        $this->deployFile('LinkEvent2.bpmn');
        
        $this->runtimeService->startProcessInstanceByKey('LinkEvent2');
        
        $tasks = $this->taskService->createTaskQuery()->orderByTaskDefinitionKey()->findAll();
        $this->assertCount(2, $tasks);
        $this->assertEquals('task1', $tasks[0]->getDefinitionKey());
        $this->assertEquals('task1', $tasks[1]->getDefinitionKey());
        
        $this->taskService->complete($tasks[0]->getId());
        $this->taskService->complete($tasks[1]->getId());
        
        $this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
    }
}
