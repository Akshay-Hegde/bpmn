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

use KoolKode\Expression\ExpressionContextInterface;
use KoolKode\Expression\ExpressionInterface;

trait BasicAttributesTrait
{
    protected $name;

    protected $documentation;

    protected $asyncBefore = false;

    protected $asyncAfter = false;

    public function setName(ExpressionInterface $name = null)
    {
        $this->name = $name;
    }

    public function setDocumentation(ExpressionInterface $documentation = null)
    {
        $this->documentation = $documentation;
    }

    public function getValue(ExpressionInterface $exp = null, ExpressionContextInterface $context = null)
    {
        return ($exp === null || $context === null) ? null : $exp($context);
    }

    public function getIntegerValue(ExpressionInterface $exp = null, ExpressionContextInterface $context = null)
    {
        return ($exp === null || $context === null) ? 0 : (int) $exp($context);
    }

    public function getStringValue(ExpressionInterface $exp = null, ExpressionContextInterface $context = null)
    {
        return ($exp === null || $context === null) ? '' : (string) $exp($context);
    }

    public function getDateValue(ExpressionInterface $exp = null, ExpressionContextInterface $context = null)
    {
        $value = ($exp === null || $context === null) ? null : $exp($context);
        
        if ($value === null) {
            return;
        }
        
        if (is_numeric($value)) {
            return new \DateTimeImmutable('@' . $value);
        }
        
        if ($value instanceof \DateTimeInterface) {
            return new \DateTimeImmutable('@' . $value->getTimestamp(), $value->getTimezone());
        }
        
        return new \DateTimeImmutable($value);
    }

    public function isAsyncBefore()
    {
        return $this->asyncBefore;
    }

    public function setAsyncBefore($async)
    {
        $this->asyncBefore = $async ? true : false;
    }

    public function isAsyncAfter()
    {
        return $this->asyncAfter;
    }

    public function setAsyncAfter($async)
    {
        $this->asyncAfter = $async ? true : false;
    }
}
