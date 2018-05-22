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

use KoolKode\Process\Node;
use KoolKode\Process\Behavior\SignalableBehaviorInterface;

/**
 * Contract for BPMN activities.
 * 
 * @author Martin Schröder
 */
interface ActivityInterface extends SignalableBehaviorInterface
{
    /**
     * Clears all event subscriptions (and related jobs) for the given execution / activity combination.
     */
    public function clearEventSubscriptions(VirtualExecution $execution, string $activityId): void;

    /**
     * Create event subscriptions.
     */
    public function createEventSubscriptions(VirtualExecution $execution, string $activityId, ?Node $node = null): void;
}
