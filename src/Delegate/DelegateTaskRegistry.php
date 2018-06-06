<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Delegate;

class DelegateTaskRegistry implements DelegateTaskFactoryInterface
{
    protected $tasks = [];

    public function registerTask(DelegateTaskInterface $task, ?string $typeName = null): void
    {
        $this->tasks[($typeName === null) ? \get_class($task) : (string) $typeName] = $task;
    }

    /**
     * {@inheritdoc}
     */
    public function createDelegateTask(string $typeName): DelegateTaskInterface
    {
        if (isset($this->tasks[$typeName])) {
            return $this->tasks[$typeName];
        }
        
        throw new \OutOfBoundsException(\sprintf('No such delegate task registered: %s', $typeName));
    }
}
