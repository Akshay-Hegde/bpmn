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

use KoolKode\BPMN\Task\TaskInterface;
use KoolKode\BPMN\Test\BusinessProcessTestCase;

class EventSubProcessTest extends BusinessProcessTestCase
{
	public function testProcessWithoutMessage()
	{
		$this->deployFile('EventSubProcessTest.bpmn');
		
		$this->runtimeService->startProcessInstanceByKey('main');
		$this->assertEquals(6, $this->runtimeService->createExecutionQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_2', $task->getActivityId());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_3', $task->getActivityId());
		$this->assertEquals(3, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	public function testProcessWithMessage()
	{
		$this->markTestSkipped('Still needs lots of improvement related to executions hierarchy / scopes');
		
		$this->deployFile('EventSubProcessTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		
		$this->assertEquals(6, $this->runtimeService->createExecutionQuery()->count());
	
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_2', $task->getActivityId());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$sub = $this->runtimeService->createExecutionQuery()->processInstanceId($process->getId())->messageEventSubscriptionName('OrderItemAddedMessage')->findAll();
		$this->assertCount(1, $sub);
		
		$this->runtimeService->messageEventReceived('OrderItemAddedMessage', $sub[0]->getId());

		foreach($this->runtimeService->createProcessInstanceQuery()->findAll() as $proc)
		{
			echo "\n";
			$this->dumpExec($this->processEngine->findExecution($proc->getId()));
			echo "\n";
		}
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_1', $task->getActivityId());
		$this->assertEquals(6, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$this->taskService->complete($task->getId());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_3', $task->getActivityId());
		$this->assertEquals(3, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
	
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	protected function dumpExec(\KoolKode\BPMN\Engine\VirtualExecution $exec)
	{
		printf("%s%s [ %s ]\n", str_repeat('  ', $exec->getExecutionDepth()), $exec->getNode()->getId(), $exec->getId());
		
		foreach($exec->findChildExecutions() as $child)
		{
			$this->dumpExec($child);
		}
	}
}
