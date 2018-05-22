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

use KoolKode\BPMN\History\HistoricProcessInstance;
use KoolKode\BPMN\Job\Handler\AsyncCommandHandler;
use KoolKode\BPMN\Repository\DeployedResource;
use KoolKode\BPMN\Repository\Deployment;
use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;
use KoolKode\BPMN\Test\BusinessProcessTestCase;
use KoolKode\BPMN\Test\MessageHandler;

class PizzaCollaborationTest extends BusinessProcessTestCase
{
    public function testPizzaProcess()
    {
        $this->jobExecutor->registerJobHandler(new AsyncCommandHandler());
        
        $deployment = $this->deployFile('PizzaCollaborationTest.bpmn');
        $this->assertTrue($deployment instanceof Deployment);
        
        $resources = $deployment->findResources();
        $this->assertCount(1, $resources);
        
        $diagram = array_pop($resources);
        $this->assertTrue($diagram instanceof DeployedResource);
        $this->assertEquals(file_get_contents(__DIR__ . '/PizzaCollaborationTest.bpmn'), $diagram->getContents());
        
        $process = $this->runtimeService->startProcessInstanceByKey('CustomerOrdersPizza', 'Pizza Funghi');
        $this->assertTrue($process instanceof HistoricProcessInstance);
        $this->assertFalse($process->isFinished());
        
        $task = $this->taskService->createTaskQuery()->findOne();
        $this->assertEquals('choosePizzaTask', $task->getDefinitionKey());
        $this->assertEquals(0, $this->managementService->createJobQuery()->count());
        
        $this->taskService->complete($task->getId(), []);
        $this->assertEquals(2, $this->managementService->createJobQuery()->count());
        $jobs = $this->managementService->createJobQuery()->timer(false)->findAll();
        $this->assertCount(1, $jobs);
        
        $this->assertEquals(2, $this->runtimeService->createEventSubscriptionQuery()->processInstanceId($process->getId())->count());
        
        $this->managementService->executeJob($jobs[0]->getId());
        $this->assertEquals(1, $this->taskService->createTaskQuery()->count());
        $task = $this->taskService->createTaskQuery()->findOne();
        $this->assertEquals('preparePizzaTask', $task->getDefinitionKey());
        
        $this->taskService->complete($task->getId(), []);
        
        $this->assertEquals(1, $this->managementService->createJobQuery()->count());
        $jobs = $this->managementService->createJobQuery()->processInstanceId($process->getId())->findAll();
        
        $this->managementService->executeJob($jobs[0]->getId());
        
        $this->assertEquals(5, $this->runtimeService->createExecutionQuery()->count());
        $this->assertEquals(1, $this->taskService->createTaskQuery()->count());
        $task = $this->taskService->createTaskQuery()->findOne();
        $this->assertEquals('fileReportTask', $task->getDefinitionKey());
        
        $process = $this->runtimeService->createExecutionQuery()->findOne();
        
        $this->taskService->complete($task->getId(), []);
        $this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
    }
    
    protected function sendPizzaOrder(): MessageHandler
    {
        return new MessageHandler('sendPizzaOrder', 'CustomerOrdersPizza', function (MessageThrownEvent $event) {
            $process = $this->runtimeService->startProcessInstanceByMessage('pizzaOrderReceived', $event->execution->getBusinessKey());
            
            $this->assertTrue($process instanceof HistoricProcessInstance);
            $this->assertEquals('PizzaServiceDeliversPizza', $process->getProcessDefinitionKey());
        });
    }
    
    protected function deliverPizza(): MessageHandler
    {
        return new MessageHandler('deliverPizza', 'PizzaServiceDeliversPizza', function (MessageThrownEvent $event) {
            $this->runtimeService->createMessageCorrelation('pizzaReceived')->processBusinessKey($event->execution->getBusinessKey())->correlate();
        });
    }
    
    protected function payForPizza(): MessageHandler
    {
        return new MessageHandler('payForPizza', 'CustomerOrdersPizza', function (MessageThrownEvent $event) {
            $this->runtimeService->createMessageCorrelation('pizzaPaymentReceived')->processBusinessKey($event->execution->getBusinessKey())->correlate();
        });
    }
}
