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

namespace KoolKode\BPMN\Repository\Command;

use KoolKode\BPMN\DiagramLoader;
use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Repository\DeploymentBuilder;
use KoolKode\Util\UUID;

class CreateDeploymentCommand extends AbstractBusinessCommand
{
    protected $builder;

    public function __construct(DeploymentBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function executeCommand(ProcessEngine $engine): UUID
    {
        $name = $this->builder->getName();
        
        if ($this->builder->count() < 1) {
            throw new \RuntimeException(\sprintf('Cannot deploy "%s" because it does not contain any resources', $name));
        }
        
        $id = UUID::createRandom();
        
        $stmt = $engine->prepareQuery("INSERT INTO `#__bpmn_deployment` (`id`, `name`, `deployed_at`) VALUES (:id, :name, :time)");
        $stmt->bindValue('id', $id);
        $stmt->bindValue('name', $name);
        $stmt->bindValue('time', \time());
        $stmt->execute();
        
        $engine->info('Created deployment "{name}" with identifier <{id}>', [
            'name' => $name,
            'id' => (string) $id
        ]);
        
        $stmt = $engine->prepareQuery("INSERT INTO `#__bpmn_resource` (`id`, `deployment_id`, `name`, `data`) VALUES (:id, :deployment, :name, :data)");
        $stmt->bindValue('deployment', $id);
        
        $parser = new DiagramLoader();
        
        foreach ($this->builder as $name => $stream) {
            $in = $stream->getContents();
            $resourceId = UUID::createRandom();
            
            $stmt->bindValue('id', $resourceId);
            $stmt->bindValue('name', $name);
            $stmt->bindValue('data', new BinaryData($in));
            $stmt->execute();
            
            $engine->debug('Deployed resource "{name}" with identifer <{resourceId}>', [
                'name' => $name,
                'resourceId' => (string) $resourceId
            ]);
            
            if ($this->builder->isProcessResource($name)) {
                foreach ($parser->parseDiagramString($in) as $process) {
                    $engine->pushCommand(new DeployBusinessProcessCommand($process, $id, $resourceId));
                }
            }
        }
        
        return $id;
    }
}
