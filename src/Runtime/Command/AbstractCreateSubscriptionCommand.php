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
use KoolKode\BPMN\Job\Job;

/**
 * Creates a message event subscription.
 * 
 * @author Martin Schröder
 */
abstract class AbstractCreateSubscriptionCommand extends AbstractBusinessCommand
{
	/**
	 * Name of the subscription type: "signal", "message", etc.
	 * 
	 * @var string
	 */
	protected $name;
	
	/**
	 * ID of the target execution.
	 * 
	 * @var UUID
	 */
	protected $executionId;
	
	/**
	 * ID of the activity that created the event subscription.
	 * 
	 * @var string
	 */
	protected $activityId;
	
	/**
	 * ID of the target node to receive the delegated signal or NULL in order to use the activity node.
	 * 
	 * @var string
	 */
	protected $nodeId;
	
	/**
	 * Is this a subscription for a boundary event?
	 * 
	 * @var boolean
	 */
	protected $boundaryEvent;
	
	/**
	 * Create a new persisted event subscription.
	 * 
	 * @param string $name Name of the subscription type: "signal", "message", etc.
	 * @param VirtualExecution $execution Target execution.
	 * @param string $activityId ID of the activity that created the event subscription.
	 * @param Node $node Target node to receive the delegated signal or NULL in order to use the activity node.
	 * @param boolean $boundaryEvent Is this a subscription for a boundary event?
	 */
	public function __construct($name, VirtualExecution $execution, $activityId, Node $node = NULL, $boundaryEvent = false)
	{
		$this->name = (string)$name;
		$this->executionId = $execution->getId();
		$this->activityId = (string)$activityId;
		$this->nodeId = ($node === NULL) ? NULL : (string)$node->getId();
		$this->boundaryEvent = $boundaryEvent ? true : false;
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function isSerializable()
	{
		return true;
	}
	
	/**
	 * Create an event subscription entry in the DB.
	 * 
	 * @param ProcessEngine $engine
	 * @param Job $job
	 */
	protected function createSubscription(ProcessEngine $engine, Job $job = NULL)
	{
		$execution = $engine->findExecution($this->executionId);
		$nodeId = ($this->nodeId === NULL) ? NULL : $execution->getProcessModel()->findNode($this->nodeId)->getId();
		
		$data = [
			'id' => UUID::createRandom(),
			'execution_id' => $execution->getId(),
			'activity_id' => $this->activityId,
			'node' => $nodeId,
			'process_instance_id' => $execution->getRootExecution()->getId(),
			'flags' => $this->getSubscriptionFlag(),
			'boundary' => $this->boundaryEvent ? 1 : 0,
			'name' => $this->name,
			'created_at' => time()
		];
		
		if($job !== NULL)
		{
			$data['job_id'] = $job->getId();
		}
		
		$engine->getConnection()->insert('#__bpmn_event_subscription', $data);
	}
	
	/**
	 * Get the value being used as flag in the subscription table.
	 * 
	 * @return integer
	 */
	protected abstract function getSubscriptionFlag();
}
