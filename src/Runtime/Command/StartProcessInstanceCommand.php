<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Repository\ProcessDefinition;
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
    public function __construct(ProcessDefinition $definition, Node $startNode, ?string $businessKey = null, array $variables = [])
    {
        $this->definitionId = $definition->getId();
        $this->startNodeId = $startNode->getId();
        $this->businessKey = $businessKey;
        $this->variables = \serialize($variables);
    }

    /**
     * {@inheritdoc}
     * 
     * @codeCoverageIgnore
     */
    public function isSerializable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ProcessEngine $engine): UUID
    {
        $def = $engine->getRepositoryService()->createProcessDefinitionQuery()->processDefinitionId($this->definitionId)->findOne();
        $definition = $def->getModel();
        
        $startNode = $definition->findNode($this->startNodeId);
        
        $process = new VirtualExecution(UUID::createRandom(), $engine, $definition);
        $process->setBusinessKey($this->businessKey);
        $process->setNode($startNode);
        
        foreach (\unserialize($this->variables) as $k => $v) {
            $process->setVariable($k, $v);
        }
        
        $engine->registerExecution($process);
        
        $engine->info('Started {process} using process definition "{key}" ({id})', [
            'process' => (string) $process,
            'key' => $def->getKey(),
            'id' => (string) $def->getId()
        ]);
        
        $process->execute($startNode);
        
        return $process->getId();
    }
}
