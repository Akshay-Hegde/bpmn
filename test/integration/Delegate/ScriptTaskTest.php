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
use KoolKode\BPMN\Repository\DeploymentBuilder;

class ScriptTaskTest extends BusinessProcessTestCase
{
    public function provider1()
    {
        return [
            [10, 0, 10],
            [40, 5, 35]
        ];
    }
    
    protected $verifiedEvent = false;

    /**
     * Test inline script with access to a delegate execution.
     * 
     * @dataProvider provider1
     */
    public function test1($amount, $discount, $result)
    {
        $this->deployFile('ScriptTask1.bpmn');
        
        $this->eventDispatcher->connect(function (TaskExecutedEvent $event) {
            $this->assertEquals('script1', $event->execution->getActivityId());
            $this->verifiedEvent = true;
        });
        
        $process = $this->runtimeService->startProcessInstanceByKey('ScriptTask1', null, [
            'amount' => $amount,
            'discount' => $discount
        ]);
        
        $this->assertTrue($this->verifiedEvent);
        
        $vars = $this->runtimeService->getExecutionVariables($process->getId());
        
        $this->assertEquals([
            'amount' => $amount,
            'discount' => $discount,
            'result' => $result
        ], $vars);
    }

    /**
     * Test script loaded from file resource within the same deployment as the process definition.
     *
     * @dataProvider provider1
     */
    public function test2($amount, $discount, $result)
    {
        $builder = new DeploymentBuilder('ScriptTask2');
        $builder->addResource('ScriptTask2.bpmn', new \SplFileInfo(__DIR__ . '/ScriptTask2.bpmn'));
        $builder->addResource('ScriptTask2.php', new \SplFileInfo(__DIR__ . '/ScriptTask2.php'));
        
        $this->repositoryService->deploy($builder);
        
        $this->eventDispatcher->connect(function (TaskExecutedEvent $event) {
            $this->assertEquals('script1', $event->execution->getActivityId());
            $this->verifiedEvent = true;
        });
        
        $process = $this->runtimeService->startProcessInstanceByKey('ScriptTask2', null, [
            'amount' => $amount,
            'discount' => $discount
        ]);
        
        $this->assertTrue($this->verifiedEvent);
        
        $vars = $this->runtimeService->getExecutionVariables($process->getId());
        
        $this->assertEquals([
            'amount' => $amount,
            'discount' => $discount,
            'result' => $result
        ], $vars);
    }
}
