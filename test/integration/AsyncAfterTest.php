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

use KoolKode\BPMN\Job\Handler\AsyncAfterHandler;
use KoolKode\BPMN\Job\JobQuery;
use KoolKode\BPMN\Test\BusinessProcessTestCase;

class AsyncAfterTest extends BusinessProcessTestCase
{
	public function provideAsyncHandler()
	{
		yield [false];
		yield [true];
	}
	
	/**
	 * @dataProvider provideAsyncHandler
	 */
	public function testAsyncBeforeTask($async)
	{
		if($async)
		{
			$this->jobExecutor->registerJobHandler(new AsyncAfterHandler());
		}
		
		$this->deployFile('AsyncAfterTest.bpmn');
		
		$this->runtimeService->startProcessInstanceByKey('process1');
		
		$process = $this->runtimeService->createExecutionQuery()->findOne();
		
		if($async)
		{
			$this->assertNull($process->getActivityId());
			
			$query = new JobQuery($this->processEngine);
			$query->executionId($process->getId());
			$query->processInstanceId($process->getId());
			$query->processDefinitionKey('process1');
			
			$this->assertEquals(1, $query->count());
			
			$job = $query->findOne();
			$this->assertEquals(AsyncAfterHandler::HANDLER_TYPE, $job->getHandlerType());
			$this->assertEquals(0, $job->getRetries());
			$this->assertFalse($job->isLocked());
			$this->assertNull($job->getLockOwner());
			
			$this->jobExecutor->executeJob($job->getId());
		}
		
		$task = $this->taskService->createTaskQuery()->executionId($process->getId())->findOne();
		$this->assertEquals('Task within job', $task->getName());
		
		$this->taskService->complete($task->getId());
		
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
