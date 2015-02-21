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

use KoolKode\BPMN\Task\TaskInterface;
use KoolKode\BPMN\Test\BusinessProcessTestCase;

class EventBaseGatewayTest extends BusinessProcessTestCase
{
	public function provider1()
	{
		return [
			['A', 'Play on Novice'],
			['B', 'Play on Medium'],
			['C', 'Play on Master']
		];
	}
	
	/**
	 * @dataProvider provider1
	 */
	public function test1($signal, $mode)
	{
		$this->deployFile('EventBasedGateway1.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('EventBasedGateway1');
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
	}
}
