<?php

declare(strict_types=1);

namespace Ksfraser\Workflow\StateMachine;

use Ksfraser\Workflow\Status\StagingStatusInterface;
use Ksfraser\Workflow\Status\StagingStatusTrait;

/**
 * Generic Staging State Machine
 * 
 * A reusable state machine for staging workflows.
 * Modules can extend this class or implement the interfaces directly.
 * 
 * @since 1.0.0
 */
class StagingStateMachine implements StagingStatusInterface, StateMachineInterface
{
    use StagingStatusTrait;
    use StateMachineTrait;

    protected array $validTransitions = [
        self::STATUS_STAGED => [
            self::STATUS_PENDING_REVIEW,
            self::STATUS_MATCHED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_ERROR,
        ],
        self::STATUS_PENDING_REVIEW => [
            self::STATUS_MATCHED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_STAGED,
            self::STATUS_ERROR,
        ],
        self::STATUS_MATCHED => [
            self::STATUS_IN_PROGRESS,
            self::STATUS_PROCESSING,
            self::STATUS_ERROR,
        ],
        self::STATUS_IN_PROGRESS => [
            self::STATUS_PROCESSED,
            self::STATUS_COMPLETED,
            self::STATUS_ERROR,
        ],
        self::STATUS_PROCESSING => [
            self::STATUS_PROCESSED,
            self::STATUS_COMPLETED,
            self::STATUS_IMPORTED,
            self::STATUS_ERROR,
        ],
        self::STATUS_PROCESSED => [
            self::STATUS_COMPLETED,
            self::STATUS_IMPORTED,
            self::STATUS_ERROR,
        ],
        self::STATUS_COMPLETED => [
            self::STATUS_IMPORTED,
            self::STATUS_ERROR,
        ],
        self::STATUS_IMPORTED => [],
        self::STATUS_ERROR => [
            self::STATUS_STAGED,
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
        ],
        self::STATUS_FAILED => [
            self::STATUS_STAGED,
            self::STATUS_PENDING,
        ],
    ];

    public function getValidTransitions(string $currentStatus): array
    {
        return $this->validTransitions[$currentStatus] ?? [];
    }

    public function getFinalStatus(): string
    {
        return self::STATUS_IMPORTED;
    }

    public function getInitialStatus(): string
    {
        return self::STATUS_STAGED;
    }

    public function setValidTransitions(array $transitions): void
    {
        $this->validTransitions = $transitions;
    }

    public function addTransition(string $fromStatus, string $toStatus): void
    {
        if (!isset($this->validTransitions[$fromStatus])) {
            $this->validTransitions[$fromStatus] = [];
        }
        if (!in_array($toStatus, $this->validTransitions[$fromStatus], true)) {
            $this->validTransitions[$fromStatus][] = $toStatus;
        }
    }

    public function removeTransition(string $fromStatus, string $toStatus): void
    {
        if (isset($this->validTransitions[$fromStatus])) {
            $this->validTransitions[$fromStatus] = array_filter(
                $this->validTransitions[$fromStatus],
                fn($s) => $s !== $toStatus
            );
        }
    }
}