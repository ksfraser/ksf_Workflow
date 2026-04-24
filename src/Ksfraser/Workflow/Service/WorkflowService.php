<?php

declare(strict_types=1);

namespace Ksfraser\Workflow\Service;

use Ksfraser\Workflow\Entity\WorkflowTrigger;
use Ksfraser\Workflow\Entity\WorkflowAction;

class WorkflowService
{
    private array $triggers = [];
    private array $actions = [];
    private bool $dbAvailable = false;

    public function __construct()
    {
        $this->dbAvailable = function_exists('db_query') && function_exists('db_escape');
    }

    public function loadTriggers(?string $entityType = null): array
    {
        $this->triggers = [];
        
        return [];
    }

    public function loadActions(int $triggerId): array
    {
        $this->actions = [];
        
        return [];
    }

    public function evaluateTriggers(string $entityType, array $entity, ?array $oldEntity = null): array
    {
        $matchingTriggers = [];

        foreach ($this->triggers as $trigger) {
            if ($trigger->entityType !== $entityType) {
                continue;
            }

            if ($trigger->evaluate($entity, $oldEntity)) {
                $matchingTriggers[] = $trigger;
            }
        }

        usort($matchingTriggers, fn($a, $b) => $b->priority <=> $a->priority);

        return $matchingTriggers;
    }

    public function executeWorkflow(int $triggerId, array &$entity, ?array $context = null): bool
    {
        $actions = $this->getActionsForTrigger($triggerId);
        
        $success = true;
        
        foreach ($actions as $action) {
            if (!$action->execute($entity, $context)) {
                $success = false;
            }
        }
        
        return $success;
    }

    public function runWorkflows(string $entityType, array $entity, ?array $oldEntity = null, ?array $context = null): array
    {
        $results = [
            'triggers_fired' => [],
            'actions_executed' => 0,
            'errors' => [],
        ];

        $matchingTriggers = $this->evaluateTriggers($entityType, $entity, $oldEntity);

        foreach ($matchingTriggers as $trigger) {
            $results['triggers_fired'][] = $trigger->id;

            if ($this->executeWorkflow($trigger->id, $entity, $context)) {
                $results['actions_executed']++;
            } else {
                $results['errors'][] = "Trigger {$trigger->id} failed";
            }
        }

        return $results;
    }

    public function addTrigger(WorkflowTrigger $trigger): void
    {
        $this->triggers[] = $trigger;
    }

    public function addAction(WorkflowAction $action): void
    {
        $this->actions[] = $action;
    }

    private function getActionsForTrigger(int $triggerId): array
    {
        return array_filter($this->actions, fn($a) => $a->triggerId === $triggerId);
    }

    public function createWorkflowFromConfig(string $name, string $entityType, array $config): WorkflowConfig
    {
        $workflow = new WorkflowConfig();
        $workflow->name = $name;
        $workflow->entityType = $entityType;

        foreach ($config['triggers'] ?? [] as $triggerData) {
            $trigger = new WorkflowTrigger();
            $trigger->name = $triggerData['name'] ?? $name . ' Trigger';
            $trigger->entityType = $entityType;
            $trigger->fieldName = $triggerData['field'] ?? '';
            $trigger->operator = $triggerData['operator'] ?? 'equals';
            $trigger->fieldValue = $triggerData['value'] ?? '';
            $trigger->triggerType = $triggerData['type'] ?? 'on_save';
            $trigger->isActive = true;

            $workflow->triggers[] = $trigger;

            foreach ($triggerData['actions'] ?? [] as $actionData) {
                $action = new WorkflowAction();
                $action->name = $actionData['name'] ?? '';
                $action->actionType = $actionData['type'] ?? 'update_field';
                $action->actionConfig = $actionData['config'] ?? [];
                $action->isActive = true;

                $workflow->actions[] = $action;
            }
        }

        return $workflow;
    }

    public function evaluateExpression(string $expression, array $entity): mixed
    {
        $vars = [];
        
        foreach ($entity as $key => $val) {
            $vars['{' . $key . '}'] = is_numeric($val) ? $val : "'" . addslashes($val) . "'";
        }
        
        $expr = strtr($expression, $vars);
        
        if (preg_match('/^(sum|avg|count|min|max)\((.*)\)$/', $expr, $m)) {
            return $this->aggregate($m[1], $m[2], $entity);
        }
        
        return eval("return $expr;") ?? null;
    }

    private function aggregate(string $func, string $field, array $entity): mixed
    {
        $values = array_filter(array_map('floatval', explode(',', $field)));
        
        return match ($func) {
            'sum' => array_sum($values),
            'avg' => array_sum($values) / count($values),
            'count' => count($values),
            'min' => min($values),
            'max' => max($values),
            default => null,
        };
    }
}

class WorkflowConfig
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $entityType = null;
    public array $triggers = [];
    public array $actions = [];
    public bool $isActive = true;
}