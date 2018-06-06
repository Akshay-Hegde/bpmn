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

namespace KoolKode\BPMN\Engine;

use KoolKode\Process\EngineInterface;
use KoolKode\Process\Command\AbstractCommand;

/**
 * Base class for a BPMN engine command.
 * 
 * @author Martin Schröder
 */
abstract class AbstractBusinessCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     * 
     * @codeCoverageIgnore
     */
    public function isSerializable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public final function execute(EngineInterface $engine)
    {
        return $this->executeCommand($engine);
    }

    /**
     * Execute the command logic using the given BPMN process engine.
     */
    protected abstract function executeCommand(ProcessEngine $engine);
}
