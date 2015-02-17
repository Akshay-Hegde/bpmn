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

use KoolKode\BPMN\Job\Handler\AsyncCommandHandler;
use KoolKode\BPMN\Test\BusinessProcessTestCase;

class AsyncContinuationTest extends BusinessProcessTestCase
{
	public function provideAsyncHandler()
	{
		yield ['AsyncBeforeTest.bpmn', false];
		yield ['AsyncBeforeTest.bpmn', true];
		yield ['AsyncAfterTest.bpmn', false];
		yield ['AsyncAfterTest.bpmn', true];
	}
	
	/**
	 * @dataProvider provideAsyncHandler
	 */
	public function testAsync($file, $async)
	{
		if($async)
		{
			$this->jobExecutor->registerJobHandler(new AsyncCommandHandler());
		}
		
		$this->deployFile($file);
		
		$this->runtimeService->startProcessInstanceByKey('process1');
		
		$process = $this->runtimeService->createExecutionQuery()->findOne();
		
		if($async)
		{
			$this->assertNull($process->getActivityId());
			
			$query = $this->managementService->createJobQuery();
			$query->executionId($process->getId());
			$query->processInstanceId($process->getId());
			$query->processDefinitionKey('process1');
			
			$this->assertEquals(1, $query->count());
			
			$job = $query->findOne();
			$this->assertEquals(AsyncCommandHandler::HANDLER_TYPE, $job->getHandlerType());
			$this->assertEquals(0, $job->getRetries());
			$this->assertFalse($job->isLocked());
			$this->assertNull($job->getLockOwner());
			
			$this->managementService->executeJob($job->getId());
		}
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertEquals('Task within job', $task->getName());
		
		$this->taskService->complete($task->getId());
		
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
