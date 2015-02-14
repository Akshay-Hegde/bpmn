<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBehavior;
use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Repository\ProcessDefinition;
use KoolKode\Process\Command\ExecuteNodeCommand;
use KoolKode\Process\Node;
use KoolKode\Util\UUID;

/**
 * Have the engine start a new process instance at the given start node.
 * 
 * @author Martin Schröder
 */
class StartProcessInstanceCommand extends AbstractBusinessCommand
{
	protected $definitionId;
	
	protected $startNodeId;
	
	protected $businessKey;
	
	protected $variables;
	
	/**
	 * Have the engine start a new process instance.
	 * 
	 * @param ProcessDefinition $definition
	 * @param Node $startNode
	 * @param string $businessKey
	 * @param array $variables
	 */
	public function __construct(ProcessDefinition $definition, Node $startNode, $businessKey = NULL, array $variables = [])
	{
		$this->definitionId = $definition->getId();
		$this->startNodeId = $startNode->getId();
		$this->businessKey = ($businessKey === NULL) ? NULL : (string)$businessKey;
		$this->variables = $variables;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function isSerializable()
	{
		return true;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function executeCommand(ProcessEngine $engine)
	{
		$def = $engine->getRepositoryService()->createProcessDefinitionQuery()->processDefinitionId($this->definitionId)->findOne();
		$definition = $def->getModel();

		$process = new VirtualExecution(UUID::createRandom(), $engine, $definition);
		$process->setBusinessKey($this->businessKey);
		
		foreach($this->variables as $k => $v)
		{
			$process->setVariable($k, $v);
		}
		
		$engine->registerExecution($process);
		
		// FIXME: Refactor deferred commands eliminating them from the engine...
		
		$startNode = $definition->findNode($this->startNodeId);
		$behavior = $startNode->getBehavior();
		
		if($behavior instanceof AbstractBehavior && $behavior->isAsyncBefore())
		{
			$process->execute($startNode);
		}
		else
		{
			$engine->pushDeferredCommand(new ExecuteNodeCommand($process, $startNode));
		}
		
		return $process->getId();
	}
}
