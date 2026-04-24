<?php

declare(strict_types=1);

namespace Ksfraser\Workflow\Entity;

class WorkflowTrigger
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $entityType = null;
    public ?string $fieldName = null;
    public ?string $operator = 'equals';
    public ?string $fieldValue = null;
    public ?string $triggerType = 'on_save';
    public bool $isActive = true;
    public ?int $priority = 0;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    public static function getValidOperators(): array
    {
        return [
            'equals',
            'not_equals',
            'contains',
            'not_contains',
            'starts_with',
            'ends_with',
            'greater_than',
            'less_than',
            'is_empty',
            'is_not_empty',
            'changes',
            'changes_from',
        ];
    }

    public static function getValidTriggerTypes(): array
    {
        return [
            'on_save',
            'on_create',
            'on_update',
            'on_delete',
            'on_field_change',
            'scheduled',
        ];
    }

    public static function getValidEntityTypes(): array
    {
        return [
            'debtor',
            'contact',
            'opportunity',
            'ticket',
            'lead',
            'quote',
            'invoice',
            'order',
            'call_log',
        ];
    }

    public function evaluate(array $entity, ?array $oldEntity = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $currentValue = $entity[$this->fieldName] ?? null;
        $oldValue = $oldEntity[$this->fieldName] ?? null;

        return match ($this->operator) {
            'equals' => $currentValue == $this->fieldValue,
            'not_equals' => $currentValue != $this->fieldValue,
            'contains' => $currentValue !== null && strpos($currentValue, $this->fieldValue) !== false,
            'not_contains' => $currentValue === null || strpos($currentValue, $this->fieldValue) === false,
            'starts_with' => $currentValue !== null && str_starts_with($currentValue, $this->fieldValue),
            'ends_with' => $currentValue !== null && str_ends_with($currentValue, $this->fieldValue),
            'greater_than' => is_numeric($currentValue) && is_numeric($this->fieldValue) && $currentValue > $this->fieldValue,
            'less_than' => is_numeric($currentValue) && is_numeric($this->fieldValue) && $currentValue < $this->fieldValue,
            'is_empty' => empty($currentValue),
            'is_not_empty' => !empty($currentValue),
            'changes' => $currentValue != $oldValue,
            'changes_from' => $oldValue == $this->fieldValue && $currentValue != $this->fieldValue,
            default => false,
        };
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'entity_type' => $this->entityType,
            'field_name' => $this->fieldName,
            'operator' => $this->operator,
            'field_value' => $this->fieldValue,
            'trigger_type' => $this->triggerType,
            'is_active' => $this->isActive,
            'priority' => $this->priority,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        $trigger = new self();
        $trigger->id = $data['id'] ?? null;
        $trigger->name = $data['name'] ?? null;
        $trigger->entityType = $data['entity_type'] ?? null;
        $trigger->fieldName = $data['field_name'] ?? null;
        $trigger->operator = $data['operator'] ?? 'equals';
        $trigger->fieldValue = $data['field_value'] ?? null;
        $trigger->triggerType = $data['trigger_type'] ?? 'on_save';
        $trigger->isActive = $data['is_active'] ?? true;
        $trigger->priority = $data['priority'] ?? 0;
        $trigger->createdAt = $data['created_at'] ?? null;
        $trigger->updatedAt = $data['updated_at'] ?? null;
        return $trigger;
    }
}