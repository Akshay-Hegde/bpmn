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
use KoolKode\BPMN\Task\TaskInterface;

class ExclusiveGatewayTest extends BusinessProcessTestCase
{
    public function provider1()
    {
        yield ['A', ['task1']];
        yield ['B', ['task2']];
        yield ['C', ['task3']];
    }
    
    /**
     * Test split behavior of exclusive gateway including default flow.
     * 
     * @dataProvider provider1
     */
    public function test1($choice, array $tasks)
    {
        $this->deployFile('ExclusiveGateway1.bpmn');
        
        $this->runtimeService->startProcessInstanceByKey('ExclusiveGateway1', null, [
            'choice' => $choice
        ]);
        
        $found = array_map(function (TaskInterface $task) {
            return $task->getDefinitionKey();
        }, $this->taskService->createTaskQuery()->findAll());
        
        sort($found);
        
        $this->assertEquals($tasks, $found);
        
        $this->taskService->complete($this->taskService->createTaskQuery()->findOne()->getId());
        $this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
    }

    /**
     * Test join behavior of exclusive gateway.
     */
    public function test2()
    {
        $this->deployFile('ExclusiveGateway2.bpmn');
        
        $this->runtimeService->startProcessInstanceByKey('ExclusiveGateway2');
        
        $found = array_map(function (TaskInterface $task) {
            return $task->getDefinitionKey();
        }, $this->taskService->createTaskQuery()->findAll());
        
        sort($found);
        
        $this->assertEquals([
            'task1',
            'task2'
        ], $found);
        
        $this->taskService->complete($this->taskService->createTaskQuery()->taskDefinitionKey('task1')->findOne()->getId());
        
        $found = array_map(function (TaskInterface $task) {
            return $task->getDefinitionKey();
        }, $this->taskService->createTaskQuery()->findAll());
        
        sort($found);
        
        $this->assertEquals([
            'task2',
            'task3'
        ], $found);
        
        $this->taskService->complete($this->taskService->createTaskQuery()->taskDefinitionKey('task2')->findOne()->getId());
        
        $found = array_map(function (TaskInterface $task) {
            return $task->getDefinitionKey();
        }, $this->taskService->createTaskQuery()->findAll());
        
        sort($found);
        
        $this->assertEquals([
            'task3',
            'task3'
        ], $found);
        
        $this->taskService->complete($this->taskService->createTaskQuery()->findOne()->getId());
        $this->assertTrue($this->runtimeService->createExecutionQuery()->count() > 0);
        
        $this->taskService->complete($this->taskService->createTaskQuery()->findOne()->getId());
        $this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
    }
}
