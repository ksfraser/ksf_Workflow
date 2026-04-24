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
    public ?array $workingEntity = null;
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
            'load_record',
            'load_related',
            'chain',
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

        $this->workingEntity = &$entity;
        
        $type = $this->actionType;
        
        if ($type === 'update_field') return $this->executeUpdateField($entity, $config);
        if ($type === 'set_field') return $this->executeSetField($entity, $config);
        if ($type === 'calculate') return $this->executeCalculate($entity, $config);
        if ($type === 'create_record') return $this->executeCreateRecord($config, $context);
        if ($type === 'trigger_event') return $this->executeTriggerEvent($config, $entity);
        if ($type === 'send_email') return $this->executeSendEmail($config, $entity);
        if ($type === 'assign_to') return $this->executeAssignTo($entity, $config);
        if ($type === 'add_note') return $this->executeAddNote($config, $entity);
        if ($type === 'webhook') return $this->executeWebhook($config, $entity);
        if ($type === 'http_request') return $this->executeHttpRequest($config, $entity);
        if ($type === 'condition') return $this->executeCondition($entity, $config);
        if ($type === 'load_record') return $this->executeLoadRecord($config, $entity, $context);
        if ($type === 'load_related') return $this->executeLoadRelated($config, $entity, $context);
        if ($type === 'chain') return $this->executeChain($config, $entity, $context);
        
        return false;
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

    private function executeLoadRecord(array $config, array $entity, ?array $context = null): bool
    {        
        $entityType = $config['entity_type'] ?? null;
        $entityId = $config['entity_id'] ?? null;
        $targetField = $config['target_field'] ?? 'loaded_record';
        
        if (!$entityType || !$entityId) {
            $entityId = $entity['id'] ?? null;
        }
        
        if ($entityId) {
            $this->workingEntity[$targetField] = ['type' => $entityType, 'id' => $entityId, 'loaded' => true];
            $entity[$targetField] = $this->workingEntity[$targetField];
            return true;
        }
        
        return false;
    }

    private function executeLoadRelated(array $config, array $entity, ?array $context = null): bool
    {
        $relation = $config['relation'] ?? null;
        $sourceField = $config['source_field'] ?? 'debtor_no';
        $targetField = $config['target_field'] ?? 'related_record';
        
        $relatedId = $entity[$sourceField] ?? null;
        
        if ($relatedId) {
            $this->workingEntity[$targetField] = [
                'relation' => $relation,
                'id' => $relatedId,
                'loaded' => true,
            ];
            $entity[$targetField] = $this->workingEntity[$targetField];
            return true;
        }
        
        return false;
    }

    private function executeChain(array $config, array $entity, ?array $context = null): bool
    {
        $actions = $config['actions'] ?? [];
        
        if (empty($actions)) {
            return false;
        }
        
        $allSuccess = true;
        
        foreach ($actions as $actionConfig) {
            $actionType = $actionConfig['type'] ?? null;
            $actionValue = $actionConfig['value'] ?? null;
            
            switch ($actionType) {
                case 'update_field':
                    $field = $actionConfig['field'] ?? null;
                    if ($field) {
                        $this->workingEntity[$field] = $actionValue;
                        $entity[$field] = $actionValue;
                    }
                    break;
                    
                case 'load_record':
                    $entityType = $actionConfig['entity_type'] ?? null;
                    $entityId = $actionConfig['entity_id'] ?? null;
                    $targetField = $actionConfig['target_field'] ?? 'loaded_record';
                    if ($entityId) {
                        $this->workingEntity[$targetField] = ['type' => $entityType, 'id' => $entityId];
                        $entity[$targetField] = $this->workingEntity[$targetField];
                    }
                    break;
                    
                case 'calculate':
                    $targetField = $actionConfig['target_field'] ?? null;
                    $expression = $actionConfig['expression'] ?? null;
                    if ($targetField && $expression) {
                        foreach ($entity as $key => $val) {
                            $expression = str_replace('{' . $key . '}', (string) $val, $expression);
                        }
                        $result = eval("return $expression;") ?? 0;
                        $this->workingEntity[$targetField] = $result;
                        $entity[$targetField] = $result;
                    }
                    break;
                    
                case 'set_field':
                    $field = $actionConfig['field'] ?? null;
                    if ($field) {
                        $this->workingEntity[$field] = $actionConfig['value'] ?? null;
                        $entity[$field] = $this->workingEntity[$field];
                    }
                    break;
                    
                default:
                    $allSuccess = false;
            }
        }
        
        return $allSuccess;
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