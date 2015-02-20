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

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Process\Node;
use KoolKode\Util\UUID;

/**
 * Creates a message event subscription.
 * 
 * @author Martin Schröder
 */
abstract class AbstractCreateSubscriptionCommand extends AbstractBusinessCommand
{
	protected $name;
	
	protected $executionId;
	
	protected $activityId;
	
	protected $nodeId;
	
	protected $boundaryEvent;
	
	public function __construct($name, VirtualExecution $execution, $activityId, Node $node = NULL, $boundaryEvent = false)
	{
		$this->name = (string)$name;
		$this->executionId = $execution->getId();
		$this->activityId = (string)$activityId;
		$this->nodeId = ($node === NULL) ? NULL : (string)$node->getId();
		$this->boundaryEvent = $boundaryEvent ? true : false;
	}
	
	public function isSerializable()
	{
		return true;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$execution = $engine->findExecution($this->executionId);
		$nodeId = ($this->nodeId === NULL) ? NULL : $execution->getProcessModel()->findNode($this->nodeId)->getId();
		
		$sql = "	INSERT INTO `#__bpmn_event_subscription`
						(`id`, `execution_id`, `activity_id`, `node`, `process_instance_id`, `flags`, `boundary`, `name`, `created_at`)
					VALUES
						(:id, :eid, :aid, :node, :pid, :flags, :boundary, :name, :created)
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('id', UUID::createRandom());
		$stmt->bindValue('eid', $execution->getId());
		$stmt->bindValue('aid', $this->activityId);
		$stmt->bindValue('node', $nodeId);
		$stmt->bindValue('pid', $execution->getRootExecution()->getId());
		$stmt->bindValue('flags', $this->getSubscriptionFlag());
		$stmt->bindValue('boundary', $this->boundaryEvent ? 1 : 0);
		$stmt->bindValue('name', $this->name);
		$stmt->bindValue('created', time());
		$stmt->execute();
		
		$engine->debug(sprintf('{execution} subscribed to %s <{name}>', $this->getSubscriptionName()), [
			'execution' => (string)$execution,
			'name' => $this->name
		]);
	}
	
	protected abstract function getSubscriptionName();
	
	protected abstract function getSubscriptionFlag();
}
