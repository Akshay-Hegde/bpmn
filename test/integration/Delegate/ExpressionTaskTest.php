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

class ExpressionTaskTest extends BusinessProcessTestCase
{
	public function provider1()
	{
		return [
			[3, 8],
			[8, 13]
		];
	}
	
	protected $verifiedEvent = false;
	
	/**
	 * Test expression accessing process variables and writing to result variable.
	 * 
	 * @dataProvider provider1
	 */
	public function test1($amount, $result)
	{
		$this->deployFile('ExpressionTask1.bpmn');
		
		$this->eventDispatcher->connect(function(TaskExecutedEvent $event) {
			$this->assertEquals('exp1', $event->execution->getActivityId());
			$this->verifiedEvent = true;
		});
		
		$process = $this->runtimeService->startProcessInstanceByKey('ExpressionTask1', NULL, [
			'amount' => $amount
		]);
		
		$this->assertTrue($this->verifiedEvent);
		
		$vars = $this->runtimeService->getExecutionVariables($process->getId());
		
		$this->assertEquals([
			'amount' => $amount,
			'result' => $result
		], $vars);
		
		$this->assertEquals([
			'start', 'exp1'
		], $this->findCompletedActivityDefinitionKeys());
	}
	
	/**
	 * Test expression method call passing variable value.
	 */
	public function test2()
	{
		$this->deployFile('ExpressionTask2.bpmn');
	
		$this->runtimeService->startProcessInstanceByKey('ExpressionTask2', NULL, [
			'amount' => 1337
		]);
		
		$this->assertTrue($this->confirmed);
	}
	
	protected $confirmed = false;
	
	public function verifyAmount($amount)
	{
		$this->assertEquals(1337, $amount);
		
		$this->confirmed = true;
	}
}
