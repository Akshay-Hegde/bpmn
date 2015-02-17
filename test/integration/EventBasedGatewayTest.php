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

class EventBaseGatewayTest extends BusinessProcessTestCase
{
	public function providePathSignals()
	{
		return [
			['A', 'Play on Novice'],
			['B', 'Play on Medium'],
			['C', 'Play on Master']
		];
	}
	
	/**
	 * @dataProvider providePathSignals
	 */
	public function testEventGate($signal, $mode)
	{
		$this->deployFile('EventBasedGatewayTest.bpmn');
		
		$task1 = $this->taskService->createTask('Unrelated task #1', 100, 'Some doc :)');
		$this->assertTrue($task1 instanceof TaskInterface);
		$this->assertEquals('Unrelated task #1', $task1->getName());
		$this->assertEquals(100, $task1->getPriority());
		$this->assertEquals('Some doc :)', $task1->getDocumentation());
		
		$process = $this->runtimeService->startProcessInstanceByKey('mk');
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('A')->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('B')->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('C')->count());
		
		$this->runtimeService->signalEventReceived($signal);
		$this->assertEquals(3, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('A')->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('B')->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('C')->count());
		
		$task = $this->taskService->createTaskQuery()->processInstanceId($process->getId())->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals($mode, $task->getName());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$this->taskService->removeTask($task1->getId());
		$this->assertEquals(0, $this->taskService->createTaskQuery()->count());
	}
}
