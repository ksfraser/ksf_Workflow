<?php

declare(strict_types=1);

namespace Ksfraser\Workflow\Entity;

class WorkflowAction
{
    public ?int $id = null;
    public ?int $triggerId = null;
    public ?string $name = null;
    public ?string $actionType = null;
    public $actionConfig = null;
    public ?int $order = 0;
    public bool $isActive = true;
    public ?string $createdAt = null;
    public ?string $updatedAt = null;

    public static function getValidActionTypes(): array
    {
        return [
            'update_field',
            'set_field',
            'calculate',
            'create_record',
            'trigger_event',
            'send_email',
            'assign_to',
            'add_note',
            'webhook',
            'http_request',
            'condition',
        ];
    }

    public static function getBuiltInFunctions(): array
    {
        return [
            'sum',
            'avg',
            'count',
            'min',
            'max',
            'concat',
            'date_add',
            'date_diff',
            'days_between',
            'business_days',
            'round',
            'floor',
            'ceiling',
            'currency_convert',
        ];
    }

    public function execute(array &$entity, ?array $context = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if (!$this->actionConfig) {
            return false;
        }

        $config = is_array($this->actionConfig) 
            ? $this->actionConfig 
            : json_decode($this->actionConfig, true) ?? [];

        return match ($this->actionType) {
            'update_field' => $this->executeUpdateField($entity, $config),
            'set_field' => $this->executeSetField($entity, $config),
            'calculate' => $this->executeCalculate($entity, $config),
            'create_record' => $this->executeCreateRecord($config, $context),
            'trigger_event' => $this->executeTriggerEvent($config, $entity),
            'send_email' => $this->executeSendEmail($config, $entity),
            'assign_to' => $this->executeAssignTo($entity, $config),
            'add_note' => $this->executeAddNote($config, $entity),
            'webhook' => $this->executeWebhook($config, $entity),
            'http_request' => $this->executeHttpRequest($config, $entity),
            'condition' => $this->executeCondition($entity, $config),
            default => false,
        };
    }

    private function executeUpdateField(array &$entity, array $config): bool
    {
        $field = $config['field'] ?? null;
        $value = $config['value'] ?? null;
        
        if ($field) {
            $entity[$field] = $value;
            return true;
        }
        return false;
    }

    private function executeSetField(array &$entity, array $config): bool
    {
        return $this->executeUpdateField($entity, $config);
    }

    private function executeCalculate(array &$entity, array $config): bool
    {
        $targetField = $config['target_field'] ?? null;
        $expression = $config['expression'] ?? null;
        
        if (!$targetField || !$expression) {
            return false;
        }

        $value = $this->evaluateExpression($expression, $entity);
        $entity[$targetField] = $value;
        
        return true;
    }

    private function evaluateExpression(string $expression, array $entity): mixed
    {
        foreach ($entity as $key => $val) {
            $expression = str_replace('{' . $key . '}', (string) $val, $expression);
        }
        
        if (preg_match('/^{.*}$/', trim($expression))) {
            $expression = trim($expression, '{}');
            if (is_numeric($expression)) {
                return $expression;
            }
        }
        
        if (strpos($expression, '*') !== false || 
            strpos($expression, '/') !== false || 
            strpos($expression, '+') !== false ||
            strpos($expression, '-') !== false) {
            try {
                return eval("return $expression;");
            } catch (Throwable $e) {
                return $expression;
            }
        }
        
        return $expression;
    }

    private function callBuiltIn(string $func, string $expression, array $entity): mixed
    {
        return match ($func) {
            'sum' => array_sum(array_map('floatval', $entity)),
            'count' => count($entity),
            'round' => round((float) $entity['value'] ?? 0),
            'floor' => floor((float) $entity['value'] ?? 0),
            'ceiling' => ceil((float) $entity['value'] ?? 0),
            default => null,
        };
    }

    private function executeCreateRecord(array $config, ?array $context): bool
    {
        return true;
    }

    private function executeTriggerEvent(array $config, array $entity): bool
    {
        return true;
    }

    private function executeSendEmail(array $config, array $entity): bool
    {
        return true;
    }

    private function executeAssignTo(array &$entity, array $config): bool
    {
        $field = $config['target_field'] ?? 'assigned_to';
        $entity[$field] = $config['user_id'] ?? null;
        return true;
    }

    private function executeAddNote(array $config, array $entity): bool
    {
        return true;
    }

    private function executeWebhook(array $config, array $entity): bool
    {
        return true;
    }

    private function executeHttpRequest(array $config, array $entity): bool
    {
        return true;
    }

    private function executeCondition(array &$entity, array $config): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'trigger_id' => $this->triggerId,
            'name' => $this->name,
            'action_type' => $this->actionType,
            'action_config' => is_string($this->actionConfig) ? $this->actionConfig : json_encode($this->actionConfig),
            'order' => $this->order,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        $action = new self();
        $action->id = $data['id'] ?? null;
        $action->triggerId = $data['trigger_id'] ?? null;
        $action->name = $data['name'] ?? null;
        $action->actionType = $data['action_type'] ?? null;
        $action->actionConfig = $data['action_config'] ?? null;
        $action->order = $data['order'] ?? 0;
        $action->isActive = $data['is_active'] ?? true;
        $action->createdAt = $data['created_at'] ?? null;
        $action->updatedAt = $data['updated_at'] ?? null;
        return $action;
    }
}