# Architecture - ksf_Workflow

## Document Information
- **Module**: ksf_Workflow
- **Version**: 1.0.0
- **Date**: 2026-05-11
- **Status**: Proposed

## 1. Architecture Overview

ksf_Workflow is a centralized workflow engine that:
- Subscribes to PSR-14 events from any module
- Routes approvals through configurable steps
- Maintains complete audit trail
- Supports escalation and delegation

## 2. Directory Structure

```
ksf_Workflow/
├── src/Ksfraser/Workflow/
│   ├── WorkflowService.php           # Main orchestrator
│   ├── Contract/
│   │   ├── WorkflowRepositoryInterface.php
│   │   └── ApproverResolverInterface.php
│   ├── Entity/
│   │   ├── WorkflowDefinition.php    # Workflow template
│   │   ├── WorkflowInstance.php      # Runtime workflow
│   │   ├── WorkflowStep.php          # Step definition
│   │   └── WorkflowStepInstance.php # Step runtime
│   ├── Event/
│   │   ├── WorkflowStartedEvent.php
│   │   ├── StepAssignedEvent.php
│   │   ├── StepDecidedEvent.php
│   │   └── WorkflowCompletedEvent.php
│   ├── Exception/
│   │   ├── WorkflowException.php
│   │   ├── WorkflowNotFoundException.php
│   │   └── ApprovalException.php
│   └── Repository/
│       └── WorkflowRepository.php
├── tests/
│   └── Unit/
└── composer.json
```

## 3. Core Components

### 3.1 WorkflowService
Central orchestrator that:
- Loads workflow definitions
- Creates workflow instances
- Routes to approvers
- Handles escalations
- Emits PSR-14 events

### 3.2 WorkflowDefinition Entity
Represents a workflow template:
```php
class WorkflowDefinition {
    private string $id;
    private string $name;
    private string $triggerEvent;
    private array $steps;  // WorkflowStep[]
    private bool $isActive;
}
```

### 3.3 ApproverResolver
Resolves approvers dynamically:
- Manager from HRM/OrgChart
- Role-based from FA
- User expression evaluation
- Delegation check

## 4. Design Patterns

| Pattern | Usage |
|---------|-------|
| **Repository** | Data access abstraction |
| **Strategy** | Approver resolution |
| **Observer/PSR-14** | Event-driven triggers |
| **State Machine** | Workflow instance status |

## 5. SOLID Principles

| Principle | Implementation |
|-----------|----------------|
| **SRP** | Each class has one job (Service, Entity, Event, Repository) |
| **OCP** | Extend via new WorkflowDefinition, not code changes |
| **LSP** | Entities implement shared interfaces |
| **ISP** | Small focused interfaces (ApproverResolverInterface, WorkflowRepositoryInterface) |
| **DIP** | Services depend on interfaces, not implementations |

## 6. External Dependencies

| Package | Purpose |
|---------|---------|
| psr/event-dispatcher ^2.0 | Event subscription/dispatch |
| psr/log ^3.0 | Logging |
| ksfraser/exceptions ^1.3 | Exception hierarchy |

## 7. Event Flow

```
[Source Module]
    │
    ▼ (emits PSR-14 event)
[WorkflowService] ──► [WorkflowDefinition] ──► [WorkflowInstance]
    │                                                  │
    │                                                  ▼
    │                                          [WorkflowStepInstance]
    │                                                  │
    ▼                                                  ▼
[ApproverResolver] ◄──── Approval Decision ──── [Approver]
    │
    ▼
[Post-Approval Actions]
```

## 8. Database Schema (fa_wf_ prefix)

### fa_wf_definitions
- id, name, description, trigger_event, steps (JSON), is_active

### fa_wf_instances
- id, workflow_id, entity_type, entity_id, current_step, status, initiator_id

### fa_wf_step_instances
- id, instance_id, step_order, assignee_id, status, decided_at, decided_by

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-11*