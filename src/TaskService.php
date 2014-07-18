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

use KoolKode\BPMN\Command\CompleteUserTaskCommand;
use KoolKode\Util\Uuid;

class TaskService
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function createTaskQuery()
	{
		return new TaskQuery($this->engine);
	}
	
	public function claim(UUID $taskId, $userId)
	{
		
	}
	
	public function unclaim(UUID $taskId)
	{
		
	}
	
	public function complete(UUID $taskId, array $variables = [])
	{
		return $this->engine->executeCommand(new CompleteUserTaskCommand($taskId, $variables));
	}
}
