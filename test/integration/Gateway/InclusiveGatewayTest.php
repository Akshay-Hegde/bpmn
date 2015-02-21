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

class InclusiveGatewayTest extends BusinessProcessTestCase
{
	public function provider1()
	{
		yield [5, ['task2']];
		yield [172, ['task1']];
		yield [47, ['task1', 'task2']];
	}
	
	/**
	 * Test split behavior of inclusive gateway.
	 * 
	 * @dataProvider provider1
	 */
	public function test1($amount, array $tasks)
	{
		$this->deployFile('InclusiveGateway1.bpmn');
		
		$this->runtimeService->startProcessInstanceByKey('InclusiveGateway1', NULL, [
			'amount' => $amount
		]);
		
		$found = array_map(function(TaskInterface $task) {
			return $task->getActivityId();
		}, $this->taskService->createTaskQuery()->findAll());
		
		sort($found);
		
		$this->assertEquals($tasks, $found);
	}
	
	public function provider2()
	{
		yield [5, ['task2', 'task3']];
		yield [10, ['task2']];
		yield [145, ['task1']];
	}
	
	/**
	 * Test split behavior of inclusive gateway including defaut flow.
	 * 
	 * @dataProvider provider2
	 */
	public function test2($amount, array $tasks)
	{
		$this->deployFile('InclusiveGateway2.bpmn');
		
		$this->runtimeService->startProcessInstanceByKey('InclusiveGateway2', NULL, [
			'amount' => $amount
		]);
		
		$found = array_map(function(TaskInterface $task) {
			return $task->getActivityId();
		}, $this->taskService->createTaskQuery()->findAll());
		
		sort($found);
		
		$this->assertEquals($tasks, $found);
	}
	
	public function provider3()
	{
		yield [1, ['task1']];
		yield [2, ['task1', 'task2']];
		yield [3, ['task1', 'task2', 'task3']];
	}
	
	/**
	 * Test join behavior of inclusive gateway.
	 * 
	 * @dataProvider provider3
	 */
	public function test3($count, array $tasks)
	{
		$this->deployFile('InclusiveGateway3.bpmn');
	
		$this->runtimeService->startProcessInstanceByKey('InclusiveGateway3', NULL, [
			'count' => $count
		]);
	
		$found = array_map(function(TaskInterface $task) {
			return $task->getActivityId();
		}, $this->taskService->createTaskQuery()->findAll());
		
		sort($found);
		
		$this->assertEquals($tasks, $found);
		
		for($i = 0; $i < $count; $i++)
		{
			$task = $this->taskService->createTaskQuery()->findOne();
			$this->taskService->complete($task->getId());
		}
		
		$this->assertEquals(0, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
