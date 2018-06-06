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

use KoolKode\BPMN\Engine\AbstractActivity;

/**
 * @author Martin Schröder
 */
class IntermediateLinkCatchBehavior extends AbstractActivity implements IntermediateCatchEventInterface
{
    protected $link;

    public function __construct(string $link)
    {
        $this->link = $link;
    }

    public function getLink(): string
    {
        return $this->link;
    }
}
