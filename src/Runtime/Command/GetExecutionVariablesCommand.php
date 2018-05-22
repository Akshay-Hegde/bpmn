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
use KoolKode\Util\UUID;

/**
 * Populates a local variable in an execution.
 * 
 * @author Martin Schröder
 */
class GetExecutionVariablesCommand extends AbstractBusinessCommand
{
    protected $executionId;

    protected $local;

    public function __construct(UUID $executionId, ?bool $local = false)
    {
        $this->executionId = $executionId;
        $this->local = $local;
    }

    /**
     * {@inheritdoc}
     * 
     * @codeCoverageIgnore
     */
    public function isSerializable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(ProcessEngine $engine): array
    {
        $execution = $engine->findExecution($this->executionId);
        
        if ($this->local) {
            return $execution->getVariablesLocal();
        }
        
        return $execution->getVariables();
    }
}
