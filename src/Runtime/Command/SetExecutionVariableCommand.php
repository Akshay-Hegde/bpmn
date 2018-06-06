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

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Util\UUID;

/**
 * Populates a local variable in a target execution.
 * 
 * @author Martin Schröder
 */
class SetExecutionVariableCommand extends AbstractBusinessCommand
{
    protected $executionId;

    protected $variableName;

    protected $variableValue;

    protected $local;

    public function __construct(UUID $executionId, string $variableName, $variableValue, ?bool $local = true)
    {
        $this->executionId = $executionId;
        $this->variableName = $variableName;
        $this->variableValue = \serialize($variableValue);
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
    public function executeCommand(ProcessEngine $engine): void
    {
        $execution = $engine->findExecution($this->executionId);
        
        if ($this->local) {
            $execution->setVariableLocal($this->variableName, \unserialize($this->variableValue));
        } else {
            $execution->setVariable($this->variableName, \unserialize($this->variableValue));
        }
    }
}
