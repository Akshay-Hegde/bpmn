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

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Engine\AbstractActivity;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Event\CheckpointReachedEvent;

/**
 * None events are used as checkpoints within a process.
 * 
 * @author Martin Schröder
 */
class IntermediateNoneEventBehavior extends AbstractActivity
{
    /**
     * {@inheritdoc}
     */
    public function enter(VirtualExecution $execution): void
    {
        $engine = $execution->getEngine();
        
        $name = $this->getStringValue($this->name, $execution->getExpressionContext());
        
        $engine->debug('{execution} reached checkpoint "{checkpoint}" ({node})', [
            'execution' => (string) $execution,
            'checkpoint' => $this->name,
            'node' => $execution->getNode()->getId()
        ]);
        
        $engine->notify(new CheckpointReachedEvent($name, new DelegateExecution($execution), $engine));
        
        $this->leave($execution);
    }
}
