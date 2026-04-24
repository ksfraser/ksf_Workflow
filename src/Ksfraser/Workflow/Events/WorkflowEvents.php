<?php

declare(strict_types=1);

namespace Ksfraser\Workflow\Events;

use Ksfraser\Event\Event;

class WorkflowTriggeredEvent extends Event
{
    public const NAME = 'workflow.triggered';
    
    private int $triggerId;
    private string $entityType;
    private int $entityId;
    
    public function __construct(int $triggerId, string $entityType, int $entityId)
    {
        $this->triggerId = $triggerId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        parent::__construct(self::NAME);
    }
    
    public function getTriggerId(): int
    {
        return $this->triggerId;
    }
    
    public function getEntityType(): string
    {
        return $this->entityType;
    }
    
    public function getEntityId(): int
    {
        return $this->entityId;
    }
}

class WorkflowActionExecutedEvent extends Event
{
    public const NAME = 'workflow.action_executed';
    
    private int $actionId;
    private string $actionType;
    private bool $success;
    
    public function __construct(int $actionId, string $actionType, bool $success)
    {
        $this->actionId = $actionId;
        $this->actionType = $actionType;
        $this->success = $success;
        parent::__construct(self::NAME);
    }
    
    public function getActionId(): int
    {
        return $this->actionId;
    }
    
    public function getActionType(): string
    {
        return $this->actionType;
    }
    
    public function isSuccess(): bool
    {
        return $this->success;
    }
}