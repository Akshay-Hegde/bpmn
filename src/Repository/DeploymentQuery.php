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

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Database\StatementInterface;
use KoolKode\Database\UUIDTransformer;
use KoolKode\Util\UUID;

class DeploymentQuery extends AbstractQuery
{
    protected $deploymentId;

    protected $deploymentName;

    protected $processDefinitionKey;

    protected $containsResource;

    protected $deployedBefore;

    protected $deployedAfter;

    protected $engine;

    public function __construct(ProcessEngine $engine)
    {
        $this->engine = $engine;
    }

    public function deploymentId($id): self
    {
        $this->populateMultiProperty($this->deploymentId, $id, function ($id) {
            return new UUID($id);
        });
        
        return $this;
    }

    public function deploymentName(string $name): self
    {
        $this->populateMultiProperty($this->deploymentName, $name);
        
        return $this;
    }

    public function processDefinitionKey(string $key): self
    {
        $this->populateMultiProperty($this->processDefinitionKey, $key);
        
        return $this;
    }

    public function containsResource($id): self
    {
        $this->populateMultiProperty($this->containsResource, $id, function ($id) {
            return new UUID($id);
        });
        
        return $this;
    }

    public function deployedBefore(\DateTimeImmutable $date): self
    {
        $this->deployedBefore = $date->getTimestamp();
        
        return $this;
    }

    public function deployedAfter(\DateTimeImmutable $date): self
    {
        $this->deployedAfter = $date->getTimestamp();
        
        return $this;
    }

    public function orderByDeploymentId(bool $ascending = true): self
    {
        $this->orderings[] = [
            'd.`id`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByDeploymentName(bool $ascending = true): self
    {
        $this->orderings[] = [
            'd.`name`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function orderByDeployed(bool $ascending = true): self
    {
        $this->orderings[] = [
            'd.`deployed_at`',
            $ascending ? 'ASC' : 'DESC'
        ];
        
        return $this;
    }

    public function count(): int
    {
        $stmt = $this->executeSql(true);
        
        return (int) $stmt->fetchNextColumn(0);
    }

    public function findOne(): Deployment
    {
        $stmt = $this->executeSql(false, 1);
        $row = $stmt->fetchNextRow();
        
        if ($row === false) {
            throw new \OutOfBoundsException(\sprintf('No matching deployment found'));
        }
        
        return $this->unserializeDeployment($row);
    }

    public function findAll(): array
    {
        $stmt = $this->executeSql(false, $this->limit, $this->offset);
        $result = [];
        
        while ($row = $stmt->fetchNextRow()) {
            $result[] = $this->unserializeDeployment($row);
        }
        
        return $result;
    }

    protected function unserializeDeployment(array $row): Deployment
    {
        return new Deployment($this->engine, $row['id'], $row['name'], new \DateTimeImmutable('@' . $row['deployed_at']));
    }

    protected function getDefaultOrderBy(): array
    {
        return [
            'd.`id`',
            'ASC'
        ];
    }

    protected function executeSql(bool $count = false, int $limit = 0, int $offset = 0): StatementInterface
    {
        if ($count) {
            $fields = 'COUNT(*) AS num';
        } else {
            $fields = 'd.*';
        }
        
        $sql = "
            SELECT $fields
            FROM `#__bpmn_deployment` AS d
        ";
        
        $alias = 1;
        $joins = [];
        $where = [];
        $params = [];
        
        $this->buildPredicate("d.`id`", $this->deploymentId, $where, $params);
        $this->buildPredicate("d.`name`", $this->deploymentName, $where, $params);
        
        if ($this->deployedBefore !== null) {
            $p1 = 'p' . \count($params);
            
            $where[] = "d.`deployed_at` < :$p1";
            $params[$p1] = $this->deployedBefore;
        }
        
        if ($this->deployedAfter !== null) {
            $p1 = 'p' . \count($params);
            
            $where[] = "d.`deployed_at` > :$p1";
            $params[$p1] = $this->deployedAfter;
        }
        
        if ($this->processDefinitionKey !== null && !empty($this->processDefinitionKey)) {
            $joins[] = "INNER JOIN `#__bpmn_process_definition` AS p$alias ON (p$alias.`deployment_id` = d.`id`)";
            
            $this->buildPredicate("p$alias.`process_key`", $this->processDefinitionKey, $where, $params);
            
            $alias++;
        }
        
        if ($this->containsResource !== null && !empty($this->containsResource)) {
            $joins[] = "INNER JOIN `#__bpmn_resource` AS r$alias ON (r$alias.`deployment_id` = d.`id`)";
            
            $this->buildPredicate("r$alias.`id`", $this->containsResource, $where, $params);
            
            $alias++;
        }
        
        foreach ($joins as $join) {
            $sql .= ' ' . $join;
        }
        
        if (!empty($where)) {
            $sql .= ' WHERE ' . \implode(' AND ', $where);
        }
        
        if (!$count) {
            $sql .= $this->buildOrderings();
        }
        
        $stmt = $this->engine->prepareQuery($sql);
        $stmt->bindAll($params);
        $stmt->transform('id', new UUIDTransformer());
        $stmt->setLimit($limit);
        $stmt->setOffset($offset);
        $stmt->execute();
        
        return $stmt;
    }
}
