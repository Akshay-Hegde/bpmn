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

use KoolKode\BPMN\Engine\ActivityInterface;

/**
 * Contract for BPMN intermediate catch events that can be used with an event based gateway.
 * 
 * @author Martin Schröder
 */
interface IntermediateCatchEventInterface extends ActivityInterface { }
