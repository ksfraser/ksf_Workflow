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

## 9. RBAC Integration (ksfraser/rbac)

### 9.1 Module Registration

ksf_Workflow registers with ksfraser/rbac:

- **record_types**: 'workflow_definition', 'workflow_instance', 'workflow_step_instance', 'workflow_log'
- **projections**: 'public', 'full'
- **allow_invite**: false
- **inheritance map**:
  - workflow_definition → (no children; standalone template entity)
  - workflow_instance → children: workflow_step_instance, workflow_log

### 9.2 Entity Projections

| Entity | PUBLIC Fields | FULL Fields |
|--------|---------------|-------------|
| WorkflowDefinition | name, description, trigger_event, is_active, created_at | + steps (JSON), version_history, internal_notes, escalation_config |
| WorkflowInstance | entity_type, entity_id, status, current_step, started_at, initiator_id | + completed_at, internal_metadata, error_log, progression_data |
| WorkflowStepInstance | step_order, assignee_id, status, decided_at, decided_by, comments | + escalated_at, escalated_to, escalation_history, internal_timestamps |
| WorkflowLog | event_type, entity_type, entity_id, user_id, created_at | + detail, metadata, ip_address, user_agent |

### 9.3 Access Model

- **System Administrator**: FULL to workflow_definitions; FULL to all instances, step instances, and logs
- **Workflow Manager / Process Owner**: FULL to definitions they own/manage; FULL to instances for owned definitions
- **Approver / Participant**: PUBLIC to assigned step_instances (read + act); PUBLIC to parent workflow_instance (read-only)
- **Initiator (End User)**: PUBLIC to own initiated instances (status tracking, read-only)
- **Auditor / Compliance Officer**: FULL read-only to workflow_log; PUBLIC to workflow_instances
- **Unprivileged User**: No access — default deny

### 9.4 SQL Enforcement JOIN Pattern

All workflow-fetching queries MUST JOIN against `0_rbac_record_access`:

```sql
SELECT wi.*, ra.projection, ra.can_edit, ra.can_delete
FROM fa_wf_instances wi
JOIN 0_rbac_record_access ra
  ON ra.record_id    = wi.id
 AND ra.record_type  = 'workflow_instance'
 AND ra.module       = 'workflow'
 AND ra.inactive     = 0
 AND ra.can_view     = 1
 AND (ra.expires_at IS NULL OR ra.expires_at > NOW())
JOIN 0_rbac_team_members tm
  ON tm.team_id  = ra.team_id
 AND tm.user_id  = :currentUserId
 AND tm.inactive = 0
 AND tm.approved = 1
WHERE NOT EXISTS (
    SELECT 1 FROM 0_rbac_deny_overrides d
    WHERE d.principal_type = 'user'
      AND d.principal_id   = :currentUserId
      AND d.module         = 'workflow'
      AND (d.record_type IS NULL OR d.record_type = 'workflow_instance')
      AND (d.record_id   IS NULL OR d.record_id   = wi.id)
      AND (d.expires_at  IS NULL OR d.expires_at  > NOW())
)
```

### 9.5 Access Inheritance

- Granting access to a `workflow_instance` cascades to all child `workflow_step_instance` records (inherit parent caps).
- Granting access to a `workflow_instance` cascades to all child `workflow_log` records (inherit parent caps).
- `workflow_definition` access is granted independently (template management vs. execution visibility are separate concerns).

### 9.6 Soft Delete & Audit Logging

- **WorkflowDefinition**: Soft-deactivated via `is_active = 0`. Hard delete is super-admin only. Deactivated definitions visible only with `can_view_deleted` type-level permission.
- **WorkflowInstance / WorkflowStepInstance**: Immutable audit records — never soft-deleted. Completion or cancellation is a status transition, not a deletion.
- **WorkflowLog**: Append-only. Application-level UPDATE and DELETE are prohibited. Log entries are written via the RBAC audit log for permission mutations, and via the module's own logger for workflow events.
- All RBAC access mutations (grant, revoke, elevation) are logged to `0_rbac_audit_log`.

### 9.7 Persons Registry Integration

The `ApproverResolver` component may integrate with ksf_CRM Persons registry:

- Resolve approvers by `person_id` via `PersonsRegistryInterface`
- Map department-based approval chains through org chart teams
- Validate delegation targets against active person records
- Integration is optional and configured per workflow definition

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-24*