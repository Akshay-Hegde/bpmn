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

class ParallelGatewayTest extends BusinessProcessTestCase
{
	/**
	 * Test split behavior of parallel gateway.
	 */
	public function test1()
	{
		$this->deployFile('ParallelGateway1.bpmn');
	
		$this->runtimeService->startProcessInstanceByKey('ParallelGateway1');
	
		$found = array_map(function(TaskInterface $task) {
			return $task->getDefinitionKey();
		}, $this->taskService->createTaskQuery()->findAll());
	
		sort($found);

		$this->assertEquals(['task1', 'task2', 'task3'], $found);

		for($i = 1; $i < count($found); $i++)
		{
			$this->taskService->complete($this->taskService->createTaskQuery()->findOne()->getId());
			$this->assertTrue($this->runtimeService->createExecutionQuery()->count() > 0);
		}
		
		$this->taskService->complete($this->taskService->createTaskQuery()->findOne()->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	/**
	 * Test join behavior of parallel gateway.
	 */
	public function test2()
	{
		$this->deployFile('ParallelGateway2.bpmn');
	
		$this->runtimeService->startProcessInstanceByKey('ParallelGateway2');
	
		$found = array_map(function(TaskInterface $task) {
			return $task->getDefinitionKey();
		}, $this->taskService->createTaskQuery()->findAll());
	
		sort($found);

		$this->assertEquals(['task1', 'task2'], $found);

		$this->taskService->complete($this->taskService->createTaskQuery()->taskDefinitionKey('task1')->findOne()->getId());
		$this->assertEquals('task2', $this->taskService->createTaskQuery()->findOne()->getDefinitionKey());

		$this->taskService->complete($this->taskService->createTaskQuery()->findOne()->getId());
		$this->assertEquals('task3', $this->taskService->createTaskQuery()->findOne()->getDefinitionKey());
		
		$this->taskService->complete($this->taskService->createTaskQuery()->findOne()->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
