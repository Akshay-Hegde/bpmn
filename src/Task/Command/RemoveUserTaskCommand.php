<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Task\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\SerializableBusinessCommandInterface;
use KoolKode\Util\UUID;

/**
 * Deletes a user task from the task list.
 * 
 * @author Martin Schröder
 */
class RemoveUserTaskCommand extends AbstractBusinessCommand implements SerializableBusinessCommandInterface
{
	protected $taskId;
	
	public function __construct(UUID $taskId)
	{
		$this->taskId = $taskId;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$task = $engine->getTaskService()
					   ->createTaskQuery()
					   ->taskId($this->taskId)
					   ->findOne();
		
		$sql = "	DELETE FROM `#__bpmn_user_task`
					WHERE `id` = :id
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('id', $task->getId());
		$stmt->execute();
		
		$engine->debug('Removed user task "{task}" with id {id}', [
			'task' => $task->getName(),
			'id' => (string)$task->getId()
		]);
	}
}