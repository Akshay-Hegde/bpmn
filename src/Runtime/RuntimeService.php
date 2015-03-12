<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Repository\ProcessDefinition;
use KoolKode\BPMN\Runtime\Command\GetExecutionVariablesCommand;
use KoolKode\BPMN\Runtime\Command\MessageEventReceivedCommand;
use KoolKode\BPMN\Runtime\Command\SetExecutionVariableCommand;
use KoolKode\BPMN\Runtime\Command\SignalEventReceivedCommand;
use KoolKode\BPMN\Runtime\Command\SignalVirtualExecutionCommand;
use KoolKode\BPMN\Runtime\Command\StartProcessInstanceCommand;
use KoolKode\Util\UUID;

class RuntimeService
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function createProcessInstanceQuery()
	{
		return new ExecutionQuery($this->engine, true);
	}
	
	public function createExecutionQuery()
	{
		return new ExecutionQuery($this->engine);
	}
	
	public function createEventSubscriptionQuery()
	{
		return new EventSubscriptionQuery($this->engine);
	}
	
	public function createMessageCorrelation($messageName)
	{
		return new MessageCorrelation($this, $messageName);
	}
	
	public function signal(UUID $executionId, array $variables = [])
	{
		$this->engine->pushCommand(new SignalVirtualExecutionCommand($executionId, NULL, $variables));
	}
	
	public function messageEventReceived($messageName, UUID $executionId, array $variables = [])
	{
		$this->engine->pushCommand(new MessageEventReceivedCommand($messageName, $executionId, $variables));
	}
	
	public function signalEventReceived($signalName, UUID $executionId = NULL, array $variables = [])
	{
		$this->engine->pushCommand(new SignalEventReceivedCommand($signalName, $executionId, $variables));
	}
	
	/**
	 * Start a process from the given definition using a singular 
	 * 
	 * @param ProcessDefinition $def
	 * @param string $businessKey
	 * @param array<string, mixed> $variables
	 * @return HistoricProcessInstance
	 */
	public function startProcessInstance(ProcessDefinition $def, $businessKey = NULL, array $variables = [])
	{
		$startNode = $def->findNoneStartEvent();
		
		$id = $this->engine->executeCommand(new StartProcessInstanceCommand($def, $startNode, $businessKey, $variables));
		
		return $this->engine->getHistoryService()->createHistoricProcessInstanceQuery()->processInstanceId($id)->findOne();
	}
	
	/**
	 * Start a process using the latest version of the given process definition.
	 * 
	 * @param string $processDefinitionKey
	 * @param string $businessKey
	 * @param array<string, mixed> $variables
	 * @return HistoricProcessInstance
	 */
	public function startProcessInstanceByKey($processDefinitionKey, $businessKey = NULL, array $variables = [])
	{
		$query = $this->engine->getRepositoryService()->createProcessDefinitionQuery();
		$def = $query->processDefinitionKey($processDefinitionKey)->latestVersion()->findOne();
		
		return $this->startProcessInstance($def, $businessKey, $variables);
	}
	
	/**
	 * Start a process instance by message start event.
	 * 
	 * @param string $messageName
	 * @param string $businessKey
	 * @param array<string, mixed> $variables
	 * @return HistoricProcessInstance
	 */
	public function startProcessInstanceByMessage($messageName, $businessKey = NULL, array $variables = [])
	{
		$query = $this->engine->getRepositoryService()->createProcessDefinitionQuery();
		$def = $query->messageEventSubscriptionName($messageName)->latestVersion()->findOne();
		$startNode = $def->findMessageStartEvent($messageName);
		
		$id = $this->engine->executeCommand(new StartProcessInstanceCommand($def, $startNode, $businessKey, $variables));
		
		return $this->engine->getHistoryService()->createHistoricProcessInstanceQuery()->processInstanceId($id)->findOne();
	}
	
	public function getExecutionVariables(UUID $executionId, $local = false)
	{
		return $this->engine->executeCommand(new GetExecutionVariablesCommand($executionId, $local));
	}
	
	public function setExecutionVariable(UUID $executionId, $variableName, $variableValue, $local = false)
	{
		$this->engine->executeCommand(new SetExecutionVariableCommand($executionId, $variableName, $variableValue, $local));
	}
}
