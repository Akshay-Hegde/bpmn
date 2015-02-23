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
	
	protected $resultVariable;
	
	protected $script;
	
	public function __construct($activityId, $language, $script)
	{
		parent::__construct($activityId);
		
		$this->language = strtolower($language);
		$this->script = (string)$script;
				
		if($this->language !== 'php')
		{
			throw new \InvalidArgumentException(sprintf('Only PHP is supported as scripting language, given "%s"', $this->language));
		}
	}
	
	public function setResultVariable($var = NULL)
	{
		$this->resultVariable = ($var === NULL) ? NULL : (string)$var;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function enter(VirtualExecution $execution)
	{
		$engine = $execution->getEngine();
		$name = $this->getStringValue($this->name, $execution->getExpressionContext());
		
		$execution->getEngine()->debug('Evaluate <{language}> script task "{task}"', [
			'language' => $this->language,
			'task' => $name
		]);
		
		// Isolate scope to prevent manipulation of local / instance variables:
		$callback = function(DelegateExecution $execution, $script) {
			return eval($script);
		};
		
		if(method_exists($callback, 'bindTo'))
		{
			$callback = $callback->bindTo(NULL, NULL);
		}
		
		$result = $callback(new DelegateExecution($execution), $this->script);
		
		if($this->resultVariable !== NULL)
		{
			$execution->setVariable($this->resultVariable, $result);
		}
		
		$engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
		
		$this->leave($execution);
	}
}
