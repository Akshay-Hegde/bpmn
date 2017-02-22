<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Delegate\Behavior;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeActivity;
use KoolKode\BPMN\Engine\VirtualExecution;

/**
 * Executes a PHP script defined in a task within a BPMN process.
 * 
 * @author Martin Schröder
 */
class ScriptTaskBehavior extends AbstractScopeActivity
{
    protected $language;

    protected $scriptResource;

    protected $script;

    protected $resultVariable;

    public function __construct($activityId)
    {
        parent::__construct($activityId);
    }

    public function setScriptResource($resource)
    {
        $this->scriptResource = (string) $resource;
    }

    public function setScript($script, $language = 'php')
    {
        $this->script = (string) $script;
        $this->language = strtolower($language);
        
        if ($this->language !== 'php') {
            throw new \InvalidArgumentException(sprintf('Only PHP is supported as scripting language, given "%s"', $this->language));
        }
    }

    public function setResultVariable($var = null)
    {
        $this->resultVariable = ($var === null) ? null : (string) $var;
    }

    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution)
    {
        $engine = $execution->getEngine();
        $name = $this->getStringValue($this->name, $execution->getExpressionContext());
        
        $engine->debug('Evaluate <{language}> script task "{task}"', [
            'language' => $this->language,
            'task' => $name
        ]);
        
        if ($this->scriptResource !== null) {
            $process = $engine->getRepositoryService()->createProcessDefinitionQuery()->processDefinitionId($execution->getProcessModel()->getId())->findOne();
            $deployment = $engine->getRepositoryService()->createDeploymentQuery()->deploymentId($process->getDeploymentId())->findOne();
            
            $resource = $deployment->findResourceById($process->getResourceId());
            
            $file = str_replace('./', '', dirname($resource->getName()) . '/' . $this->scriptResource);
            $script = '?>' . $deployment->findResource($file)->getContents();
        } else {
            $script = $this->script;
        }
        
        // Isolate scope to prevent manipulation of local / instance variables:
        $callback = function (DelegateExecution $execution, $script) {
            return eval($script);
        };
        
        if (method_exists($callback, 'bindTo')) {
            $callback = $callback->bindTo(null, null);
        }
        
        $result = $callback(new DelegateExecution($execution), $script);
        
        if ($this->resultVariable !== null) {
            $execution->setVariable($this->resultVariable, $result);
        }
        
        $engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
        
        $this->leave($execution);
    }
}
