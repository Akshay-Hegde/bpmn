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

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Runtime\EventSubscription;

/**
 * Creates a signal event subscription.
 * 
 * @author Martin Schröder
 */
class CreateSignalSubscriptionCommand extends AbstractCreateSubscriptionCommand
{
    /**
     * {@inheritdoc}
     */
    public function executeCommand(ProcessEngine $engine): void
    {
        $this->createSubscription($engine);
        
        $engine->debug('{execution} subscribed to signal <{name}>', [
            'execution' => (string) $engine->findExecution($this->executionId),
            'name' => $this->name
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getSubscriptionFlag(): int
    {
        return EventSubscription::TYPE_SIGNAL;
    }
}
