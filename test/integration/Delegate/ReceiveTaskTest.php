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

class ReceiveTaskTest extends BusinessProcessTestCase
{
	protected $verifiedEvent = false;
		
	/**
	 * Test wait state and signal processing of receive task.
	 */
	public function test1()
	{
		$this->deployFile('ReceiveTask1.bpmn');
		
		$this->eventDispatcher->connect(function(TaskExecutedEvent $event) {
			$this->assertEquals('receive1', $event->execution->getActivityId());
			$this->verifiedEvent = true;
		});
		
		$process = $this->runtimeService->startProcessInstanceByKey('ReceiveTask1');
		$this->assertEquals(['start'], $this->findCompletedActivityDefinitionKeys());
		
		foreach($this->runtimeService->createExecutionQuery()->findAll() as $execution)
		{
			if(!$execution->isScope() && $execution->isWaiting())
			{
				$this->runtimeService->signal($execution->getId(), ['test' => str_replace('\\', '.', __CLASS__)]);
			}
		}
		
		$this->assertTrue($this->verifiedEvent);
		$this->assertEquals(['start', 'receive1'], $this->findCompletedActivityDefinitionKeys());
		$this->assertEquals([
			'test' => str_replace('\\', '.', __CLASS__)
		], $this->runtimeService->getExecutionVariables($process->getId()));
	}
}
