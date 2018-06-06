<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

use KoolKode\Process\Execution;
use KoolKode\Process\ProcessModel;
use KoolKode\Process\Transition;
use KoolKode\Util\UUID;

/**
 * PVM execution being used to automate BPMN 2.0 processes.
 * 
 * @author Martin Schröder
 */
class VirtualExecution extends Execution
{
    protected $businessKey;

    public function __construct(UUID $id, ProcessEngine $engine, ProcessModel $model, ?VirtualExecution $parentExecution = null)
    {
        parent::__construct($id, $engine, $model, $parentExecution);
        
        if ($parentExecution !== null) {
            $this->businessKey = $parentExecution->getBusinessKey();
        }
    }

    public function collectSyncData(): array
    {
        $data = parent::collectSyncData();
        
        $data['businessKey'] = $this->businessKey;
        
        return $data;
    }

    public function setParentExecution(?VirtualExecution $parent): void
    {
        if ($parent !== null) {
            $this->parentExecution = $parent;
            
            $parent->registerChildExecution($this);
        }
        
        $this->markModified();
    }

    /**
     * Get the BPMN process engine instance.
     */
    public function getEngine(): ProcessEngine
    {
        return parent::getEngine();
    }

    public function getBusinessKey(): ?string
    {
        return $this->businessKey;
    }

    public function setBusinessKey(?string $businessKey): void
    {
        $this->businessKey = $businessKey;
        
        $this->markModified();
    }

    public function setExecutionState(int $state): void
    {
        $this->state = $state;
    }

    public function setTransition(Transition $trans = null): void
    {
        $this->transition = $trans;
    }

    protected function injectVariablesLocal(array $variables): void
    {
        $this->variables = $variables;
    }
}
