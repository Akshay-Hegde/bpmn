<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Repository;

use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\Util\UUID;

class DeployedResource implements \JsonSerializable
{
	protected $id;
	
	protected $name;
	
	protected $deployment;
	
	public function __construct(Deployment $deployment, UUID $id, $name)
	{
		$this->deployment = $deployment;
		$this->id = $id;
		$this->name = (string)$name;
	}
	
	public function jsonSerialize()
	{
		return [
			'id' => (string)$this->id,
			'name' => $this->name
		];
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getDeployment()
	{
		return $this->deployment;
	}
	
	public function getProcessEngine()
	{
		return $this->deployment->getProcessEngine();
	}
	
	public function getContents()
	{
		$stmt = $this->deployment->getProcessEngine()->prepareQuery("
			SELECT `data` FROM `#__bpmn_resource` WHERE `id` = :id
		");
		$stmt->bindValue('id', $this->id);
		$stmt->execute();
		
		if(false === ($row = $stmt->fetchNextRow()))
		{
			throw new \OutOfBoundsException(sprintf('Resource %s not found in repository', $this->id));
		}
		
		return BinaryData::decode($row['data']);
	}
}
