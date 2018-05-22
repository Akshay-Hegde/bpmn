<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Engine\AbstractActivity;

/**
 * Basic start event without any triggers or conditions.
 * 
 * @author Martin Schröder
 */
class NoneStartEventBehavior extends AbstractActivity implements StartEventBehaviorInterface
{
    protected $subProcessStart;

    public function __construct(bool $subProcessStart = false)
    {
        $this->subProcessStart = $subProcessStart;
    }

    public function isSubProcessStart(): bool
    {
        return $this->subProcessStart;
    }

    public function isInterrupting(): bool
    {
        return false;
    }
}
