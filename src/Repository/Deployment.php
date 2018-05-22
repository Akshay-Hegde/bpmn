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

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class Deployment implements \JsonSerializable
{
    protected $id;

    protected $name;

    protected $deployDate;

    protected $resources;

    protected $engine;

    public function __construct(ProcessEngine $engine, UUID $id, string $name, \DateTimeImmutable $deployDate)
    {
        $this->engine = $engine;
        $this->id = $id;
        $this->name = $name;
        $this->deployDate = $deployDate;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'deployDate' => $this->deployDate->format(\DateTime::ISO8601)
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

    public function getDeployDate(): \DateTimeImmutable
    {
        return $this->deployDate;
    }

    public function getProcessEngine(): ProcessEngine
    {
        return $this->engine;
    }

    public function findResourceById(UUID $id): DeployedResource
    {
        foreach ($this->findResources() as $resource) {
            if ($resource->getId() == $id) {
                return $resource;
            }
        }
        
        throw new \OutOfBoundsException(sprintf('Resource %s not found in deployment %s', $id, $this->id));
    }

    public function findResource(string $name): DeployedResource
    {
        foreach ($this->findResources() as $n => $resource) {
            if ($n == $name) {
                return $resource;
            }
        }
        
        throw new \OutOfBoundsException(sprintf('Resource "%s" not found in deployment %s', $name, $this->id));
    }

    public function findResources(): array
    {
        if ($this->resources === null) {
            $this->resources = [];
            
            $sql = "
                SELECT `id`, `name`
                FROM `#__bpmn_resource`
                WHERE `deployment_id` = :id
                ORDER BY `name`
            ";
            $stmt = $this->engine->prepareQuery($sql);
            $stmt->bindValue('id', $this->id);
            $stmt->transform('id', new UUIDTransformer());
            $stmt->execute();
            
            while (false !== ($row = $stmt->fetchNextRow())) {
                $this->resources[$row['name']] = new DeployedResource($this, $row['id'], $row['name']);
            }
        }
        
        return $this->resources;
    }
}
