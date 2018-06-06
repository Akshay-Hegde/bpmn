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

namespace KoolKode\BPMN\Repository;

use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Util\UUID;

class DeployedResource implements \JsonSerializable
{
    protected $id;

    protected $name;

    protected $deployment;

    public function __construct(Deployment $deployment, UUID $id, string $name)
    {
        $this->deployment = $deployment;
        $this->id = $id;
        $this->name = $name;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name
        ];
    }

    public function getId(): UUID
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDeployment(): Deployment
    {
        return $this->deployment;
    }

    public function getProcessEngine(): ProcessEngine
    {
        return $this->deployment->getProcessEngine();
    }

    public function getContents()
    {
        $sql = "SELECT `data` FROM `#__bpmn_resource` WHERE `id` = :id";
        $stmt = $this->deployment->getProcessEngine()->prepareQuery($sql);
        $stmt->bindValue('id', $this->id);
        $stmt->execute();
        
        if (false === ($row = $stmt->fetchNextRow())) {
            throw new \OutOfBoundsException(\sprintf('Resource %s not found in repository', $this->id));
        }
        
        return BinaryData::decode($row['data']);
    }
}
