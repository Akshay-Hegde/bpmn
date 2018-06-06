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

namespace KoolKode\BPMN\Job;

use KoolKode\Util\UUID;

interface JobInterface
{
    public function getId(): UUID;
    
    public function getExecutionId(): UUID;

    /**
     * Get external ID, that is an ID used by a system like a queue to trace messages.
     */
    public function getExternalId(): ?string;

    public function getHandlerType(): string;

    public function getRetries(): int;

    public function isLocked(): bool;

    public function getLockOwner(): ?string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getScheduledAt(): ?\DateTimeImmutable;

    public function getRunAt(): ?\DateTimeImmutable;

    public function getLockedAt(): ?\DateTimeImmutable;

    public function isFailed(): bool;

    public function getExceptionType(): ?string;

    public function getExceptionMessage(): ?string;

    public function getExceptionData(): ?string;
}
