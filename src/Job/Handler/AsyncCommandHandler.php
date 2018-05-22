<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Job\Handler;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Job\Job;
use KoolKode\Process\Command\CommandInterface;

/**
 * Have the engine execute a serializable command within a job.
 * 
 * @author Martin Schröder
 */
class AsyncCommandHandler implements JobHandlerInterface
{
    /**
     * Name of the job handler.
     * 
     * @var string
     */
    const HANDLER_TYPE = 'async-command';

    /**
     * Serialized command data.
     * 
     * @var string
     */
    const PARAM_COMMAND = 'command';

    /**
     * Optional: ID of the node that the execution will move to before executing the command.
     * 
     * @var string
     */
    const PARAM_NODE_ID = 'nodeId';

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return self::HANDLER_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function executeJob(Job $job, VirtualExecution $execution, ProcessEngine $engine): void
    {
        if ($execution->isTerminated()) {
            throw new \RuntimeException(sprintf('%s is terminated', $execution));
        }
        
        $data = (array) $job->getHandlerData();
        $command = $data[self::PARAM_COMMAND];
        
        if (!$command instanceof CommandInterface) {
            throw new \RuntimeException(sprintf('Expecting command, given %s', is_object($command) ? get_class($command) : gettype($command)));
        }
        
        // Move execution to start node if param is set.
        if (array_key_exists(self::PARAM_NODE_ID, $data)) {
            $execution->setNode($execution->getProcessModel()->findNode($data[self::PARAM_NODE_ID]));
            
            $engine->debug('Moved {execution} to node "{node}"', [
                'execution' => (string) $execution,
                'node' => $execution->getNode()->getId()
            ]);
        }
        
        $engine->debug('Executing async command {cmd} using {execution}', [
            'cmd' => get_class($command),
            'execution' => (string) $execution
        ]);
        
        $engine->pushCommand($command);
    }
}
