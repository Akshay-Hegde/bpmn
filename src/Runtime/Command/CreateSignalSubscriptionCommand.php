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
 * Creates a signal event subscription.
 * 
 * @author Martin Schröder
 */
class CreateSignalSubscriptionCommand extends AbstractBusinessCommand
{
	protected $signal;
	
	protected $executionId;
	
	protected $activityId;
	
	protected $nodeId;
	
	public function __construct($signal, VirtualExecution $execution, $activityId, Node $node = NULL)
	{
		$this->signal = (string)$signal;
		$this->executionId = $execution->getId();
		$this->activityId = (string)$activityId;
		$this->nodeId = ($node === NULL) ? NULL : (string)$node->getId();
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
						(`id`, `execution_id`, `activity_id`, `node`, `process_instance_id`, `flags`, `name`, `created_at`)
					VALUES
						(:id, :eid, :aid, :node, :pid, :flags, :signal, :created)
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('id', UUID::createRandom());
		$stmt->bindValue('eid', $execution->getId());
		$stmt->bindValue('aid', $this->activityId);
		$stmt->bindValue('node', $nodeId);
		$stmt->bindValue('pid', $execution->getRootExecution()->getId());
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_SIGNAL);
		$stmt->bindValue('signal', $this->signal);
		$stmt->bindValue('created', time());
		$stmt->execute();
		
		$engine->debug('{execution} subscribed to signal <{signal}>', [
			'execution' => (string)$execution,
			'signal' => $this->signal
		]);
	}
}
