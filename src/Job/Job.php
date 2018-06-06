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

class Job implements JobInterface, \JsonSerializable
{
    protected $id;

    protected $executionId;

    protected $externalId;

    protected $handlerType;

    protected $handlerData;

    protected $retries;

    protected $locked = false;

    protected $lockOwner;

    protected $createdAt;

    protected $scheduledAt;

    protected $lockedAt;

    protected $runAt;

    protected $exceptionType;

    protected $exceptionMessage;

    protected $exceptionData;

    public function __construct(UUID $id, UUID $executionId, string $handlerType, $handlerData, ?\DateTimeImmutable $createdAt = null, ?int $retries = 0, ?string $lockOwner = null)
    {
        $this->id = $id;
        $this->executionId = $executionId;
        $this->handlerType = $handlerType;
        $this->handlerData = $handlerData;
        $this->createdAt = $createdAt;
        $this->retries = $retries;
        $this->lockOwner = $lockOwner;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'execution_id' => $this->executionId,
            'externalId' => $this->executionId,
            'handlerType' => $this->handlerType,
            'retries' => $this->retries,
            'scheduledAt' => ($this->scheduledAt === null) ? null : $this->scheduledAt->format(\DateTime::ISO8601),
            'runAt' => ($this->runAt === null) ? null : $this->runAt->format(\DateTime::ISO8601)
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): UUID
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionId(): UUID
    {
        return $this->executionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $id): void
    {
        $this->externalId = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlerType(): string
    {
        return $this->handlerType;
    }

    public function getHandlerData()
    {
        return $this->handlerData;
    }

    /**
     * {@inheritdoc}
     */
    public function getRetries(): int
    {
        return $this->retries;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): void
    {
        $this->locked = $locked;
    }

    /**
     * {@inheritdoc}
     */
    public function getLockOwner(): ?string
    {
        return $this->lockOwner;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): void
    {
        $this->scheduledAt = $scheduledAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getRunAt(): ?\DateTimeImmutable
    {
        return $this->runAt;
    }

    public function setRunAt(?\DateTimeImmutable $runAt): void
    {
        $this->runAt = $runAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getLockedAt(): ?\DateTimeImmutable
    {
        return $this->lockedAt;
    }

    public function setLockedAt(?\DateTimeImmutable $lockedAt): void
    {
        $this->lockedAt = $lockedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function isFailed(): bool
    {
        return $this->exceptionType !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionType(): ?string
    {
        return $this->exceptionType;
    }

    public function setExceptionType(?string $exceptionType): void
    {
        $this->exceptionType = $exceptionType;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionMessage(): ?string
    {
        return $this->exceptionMessage;
    }

    public function setExceptionMessage(?string $exceptionMessage): void
    {
        $this->exceptionMessage = $exceptionMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionData(): ?string
    {
        return $this->exceptionData;
    }

    public function setExceptionData(?string $exceptionData): void
    {
        $this->exceptionData = $exceptionData;
    }
}
