<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function __construct(UUID $id, UUID $executionId, $handlerType, $handlerData, \DateTimeInterface $createdAt = null, $retries = 0, $lockOwner = null)
    {
        $this->id = $id;
        $this->executionId = $executionId;
        $this->handlerType = (string) $handlerType;
        $this->handlerData = $handlerData;
        $this->createdAt = ($createdAt === null) ? new \DateTimeImmutable('now') : new \DateTimeImmutable('@' . $createdAt->getTimestamp());
        $this->retries = (int) $retries;
        $this->lockOwner = ($lockOwner === null) ? null : (string) $lockOwner;
    }

    public function jsonSerialize()
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getExecutionId()
    {
        return $this->executionId;
    }

    /**
     * {@inheritdoc}
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    public function setExternalId($id = null)
    {
        $this->externalId = ($id === null) ? null : (string) $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlerType()
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
    public function getRetries()
    {
        return $this->retries;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocked()
    {
        return $this->locked;
    }

    public function setLocked($locked)
    {
        $this->locked = $locked ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getLockOwner()
    {
        return $this->lockOwner;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledAt()
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeInterface $scheduledAt = null)
    {
        if ($scheduledAt === null) {
            $this->scheduledAt = null;
        } else {
            $this->scheduledAt = new \DateTimeImmutable('@' . $scheduledAt->getTimestamp(), new \DateTimeZone('UTC'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRunAt()
    {
        return $this->runAt;
    }

    public function setRunAt(\DateTimeInterface $runAt = null)
    {
        if ($runAt === null) {
            $this->runAt = null;
        } else {
            $this->runAt = new \DateTimeImmutable('@' . $runAt->getTimestamp(), new \DateTimeZone('UTC'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLockedAt()
    {
        return $this->lockedAt;
    }

    public function setLockedAt(\DateTimeInterface $lockedAt = null)
    {
        if ($lockedAt === null) {
            $this->lockedAt = null;
        } else {
            $this->lockedAt = new \DateTimeImmutable('@' . $lockedAt->getTimestamp(), new \DateTimeZone('UTC'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isFailed()
    {
        return $this->exceptionType !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionType()
    {
        return $this->exceptionType;
    }

    public function setExceptionType($exceptionType = null)
    {
        $this->exceptionType = ($exceptionType === null) ? null : (string) $exceptionType;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionMessage()
    {
        return $this->exceptionMessage;
    }

    public function setExceptionMessage($exceptionMessage = null)
    {
        $this->exceptionMessage = ($exceptionMessage === null) ? null : (string) $exceptionMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function getExceptionData()
    {
        return $this->exceptionData;
    }

    public function setExceptionData($exceptionData = null)
    {
        $this->exceptionData = ($exceptionData === null) ? null : (string) $exceptionData;
    }
}
