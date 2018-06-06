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

use KoolKode\BPMN\ManagementService;
use KoolKode\BPMN\History\HistoryService;
use KoolKode\BPMN\Repository\RepositoryService;
use KoolKode\BPMN\Runtime\RuntimeService;
use KoolKode\BPMN\Task\TaskService;

/**
 * Provides the public API of a BPMN 2.0 process engine.
 * 
 * @author Martin Schröder
 */
interface ProcessEngineInterface
{
    public function getRepositoryService(): RepositoryService;

    public function getRuntimeService(): RuntimeService;

    public function getTaskService(): TaskService;

    public function getHistoryService(): HistoryService;

    public function getManagementService(): ManagementService;
}
