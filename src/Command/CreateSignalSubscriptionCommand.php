<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Command;

use KoolKode\BPMN\CommandContext;
use KoolKode\BPMN\InternalExecution;
use KoolKode\BPMN\ProcessEngine;
use KoolKode\Util\Uuid;

class CreateSignalSubscriptionCommand extends AbstractCommand
{
	protected $signal;
	
	protected $execution;
	
	public function __construct($signal, InternalExecution $execution)
	{
		$this->signal = (string)$signal;
		$this->execution = $execution;
	}
	
	public function getPriority()
	{
		return 1500;
	}
	
	public function execute(CommandContext $context)
	{
		$conn = $context->getDatabaseConnection();
		
		$sql = "	INSERT INTO `#__bpm_event_subscription`
						(`id`, `execution_id`, `process_instance_id`, `flags`, `name`, `created_at`)
					VALUES
						(:id, :eid, :pid, :flags, :signal, :created)
		";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue('id', UUID::createRandom()->toBinary());
		$stmt->bindValue('eid', $this->execution->getId()->toBinary());
		$stmt->bindValue('pid', $this->execution->getProcessInstance()->getId()->toBinary());
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_SIGNAL);
		$stmt->bindValue('signal', $this->signal);
		$stmt->bindValue('created', time());
		$stmt->execute();
	}
}
