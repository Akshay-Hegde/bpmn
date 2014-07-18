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

use KoolKode\BPMN\Command\MessageEventReceivedCommand;
use KoolKode\BPMN\Command\SignalEventReceivedCommand;
use KoolKode\BPMN\Command\SignalExecutionCommand;
use KoolKode\BPMN\Command\StartProcessInstanceCommand;
use KoolKode\BPMN\Command\UpdateBusinessKeyCommand;
use KoolKode\Util\Uuid;

class RuntimeService
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function createProcessInstanceQuery()
	{
		throw new \RuntimeException('N/A');
	}
	
	public function createExecutionQuery()
	{
		return new ExecutionQuery($this->engine);
	}
	
	public function signal(UUID $executionId, array $variables = [])
	{
		$execution = $this->createExecutionQuery()->executionId($executionId)->findOne();
		
		$this->engine->executeCommand(new SignalExecutionCommand($execution, NULL, $variables));
	}
	
	public function messageEventReceived($messageName, UUID $executionId, array $variables = [])
	{
		$this->engine->executeCommand(new MessageEventReceivedCommand($messageName, $executionId, $variables));
	}
	
	public function signalEventReceived($signalName, UUID $executionId = NULL, array $variables = [])
	{
		$this->engine->executeCommand(new SignalEventReceivedCommand($signalName, $executionId, $variables));
	}
	
	public function startProcessInstanceByKey($processDefinitionKey, $businessKey = NULL, array $variables = [])
	{
		$query = $this->engine->getRepositoryService()->createProcessDefinitionQuery();
		$def = $query->processDefinitionKey($processDefinitionKey)->latestVersion()->findOne();
		
		return $this->engine->executeCommand(new StartProcessInstanceCommand($def, NULL, $businessKey, $variables));
	}
	
	public function startProcessInstanceByMessage($messageName, $businessKey = NULL, array $variables = [])
	{
		$query = $this->engine->getRepositoryService()->createProcessDefinitionQuery();
		$def = $query->messageEventSubscriptionName($messageName)->latestVersion()->findOne();
		$type = [StartProcessInstanceCommand::START_TYPE_MESSAGE => $messageName];
		
		return $this->engine->executeCommand(new StartProcessInstanceCommand($def, $type, $businessKey, $variables));
	}
	
	public function updateBusinessKey(UUID $processInstanceId, $businessKey)
	{
		$process = $this->createExecutionQuery()->executionId($processInstanceId)->processInstanceId($processInstanceId)->findOne();
		
		$this->engine->executeCommand(new UpdateBusinessKeyCommand($process->getId(), $businessKey));
	}
}
