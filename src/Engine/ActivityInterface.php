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

use KoolKode\Process\Behavior\SignalableBehaviorInterface;
use KoolKode\Process\Node;

/**
 * Contract for BPMN activities.
 * 
 * @author Martin Schröder
 */
interface ActivityInterface extends SignalableBehaviorInterface
{
	/**
	 * Clears all event subscriptions (and related jobs) for the given execution / activity combination.
	 * 
	 * @param VirtualExecution $execution
	 * @param string $activityId
	 */
	public function clearEventSubscriptions(VirtualExecution $execution, $activityId);
	
	/**
	 * Create event subscriptions.
	 * 
	 * @param VirtualExecution $execution
	 * @param string $activityId
	 * @param Node $node
	 */
	public function createEventSubscriptions(VirtualExecution $execution, $activityId, Node $node = NULL);
}
