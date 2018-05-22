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

    public function setName(?ExpressionInterface $name): void
    {
        $this->name = $name;
    }

    public function setDocumentation(?ExpressionInterface $documentation): void
    {
        $this->documentation = $documentation;
    }

    public function getValue(?ExpressionInterface $exp = null, ?ExpressionContextInterface $context = null)
    {
        return ($exp === null || $context === null) ? null : $exp($context);
    }

    public function getIntegerValue(?ExpressionInterface $exp = null, ?ExpressionContextInterface $context = null): int
    {
        return ($exp === null || $context === null) ? 0 : (int) $exp($context);
    }

    public function getStringValue(?ExpressionInterface $exp = null, ?ExpressionContextInterface $context = null): string
    {
        return ($exp === null || $context === null) ? '' : (string) $exp($context);
    }

    public function getDateValue(ExpressionInterface $exp = null, ExpressionContextInterface $context = null): ?\DateTimeImmutable
    {
        $value = ($exp === null || $context === null) ? null : $exp($context);
        
        if ($value === null) {
            return null;
        }
        
        if (is_numeric($value)) {
            return new \DateTimeImmutable('@' . $value);
        }
        
        if ($value instanceof \DateTimeImmutable) {
            return new \DateTimeImmutable('@' . $value->getTimestamp(), $value->getTimezone());
        }
        
        return new \DateTimeImmutable($value);
    }

    public function isAsyncBefore(): bool
    {
        return $this->asyncBefore;
    }

    public function setAsyncBefore(bool $async): void
    {
        $this->asyncBefore = $async ? true : false;
    }

    public function isAsyncAfter(): bool
    {
        return $this->asyncAfter;
    }

    public function setAsyncAfter(bool $async): void
    {
        $this->asyncAfter = $async ? true : false;
    }
}
