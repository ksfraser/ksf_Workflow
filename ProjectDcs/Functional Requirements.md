# Functional Requirements - ksf_Workflow

## Document Information
- **Module**: ksf_Workflow
- **Version**: 1.0.0
- **Date**: 2026-05-11
- **Status**: Proposed
- **Author**: KSFII Development Team

## 1. Overview

### 1.1 Purpose
ksf_Workflow provides a centralized workflow engine for routing approvals, automating processes, and orchestrating multi-step business operations.

### 1.2 Scope
- YAML/JSON workflow definitions
- PSR-14 event-driven triggers
- Multi-step approval chains
- Conditional routing
- Escalation rules
- Complete audit trail

## 2. Core Entities

### 2.1 WorkflowDefinition

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string | Yes | UUID |
| name | string | Yes | Workflow name |
| description | string | No | Description |
| trigger_event | string | Yes | Event that triggers workflow |
| steps | array | Yes | Array of WorkflowStep |
| is_active | bool | Yes | Default true |
| created_at | DateTime | Yes | Auto |
| updated_at | DateTime | Yes | Auto |

### 2.2 WorkflowStep

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| step_order | int | Yes | Order of execution |
| step_type | string | Yes | approval, condition, action, parallel |
| approver_type | string | No | manager, role, user, expression |
| approver_value | string | No | Manager ID, role name, user ID, or expression |
| timeout_hours | int | No | Hours before escalation |
| timeout_action | string | No | escalate, auto_approve, auto_reject |
| conditions | array | No | Array of Condition |
| actions | array | No | Array of Action |

### 2.3 WorkflowInstance

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string | Yes | UUID |
| workflow_id | string | Yes | FK to WorkflowDefinition |
| entity_type | string | Yes | Source entity type |
| entity_id | string | Yes | Source entity ID |
| current_step | int | Yes | Current step index |
| status | string | Yes | pending, in_progress, completed, rejected, cancelled |
| initiator_id | string | Yes | User who started |
| started_at | DateTime | Yes | Auto |
| completed_at | DateTime | No | Auto on completion |

### 2.4 WorkflowStepInstance

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string | Yes | UUID |
| instance_id | string | Yes | FK to WorkflowInstance |
| step_order | int | Yes | Step number |
| assignee_id | string | No | Assigned user |
| status | string | Yes | pending, approved, rejected, skipped, escalated |
| decided_at | DateTime | No | When decision made |
| decided_by | string | No | User who decided |
| comments | string | No | Decision comments |
| escalated_at | DateTime | No | When escalated |
| escalated_to | string | No | Escalation target |

## 3. Functional Requirements

### FR-WF-001: Workflow Definition CRUD
**Requirement**: System shall allow creating, reading, updating, and deleting workflow definitions.

**Features**:
- Create workflow with name, description, trigger event
- Add/remove/reorder steps
- Save as draft or activate
- Clone existing workflow
- Version history

### FR-WF-002: Event-Driven Trigger
**Requirement**: System shall listen for PSR-14 events and start workflows when matching trigger.

**Features**:
- Subscribe to any PSR-14 event type
- Match by event name or content
- Extract entity context from event
- Start workflow instance automatically
- Prevent duplicate instances for same entity

### FR-WF-003: Multi-Step Approval
**Requirement**: System shall support sequential approval steps.

**Features**:
- Define ordered approval steps
- Route to appropriate approver per step
- Require all steps to complete (sequential gate)
- Allow step skipping based on conditions
- Support parallel approval steps

### FR-WF-004: Approver Resolution
**Requirement**: System shall resolve approvers based on configuration.

**Methods**:
- **Direct User**: Specific user ID
- **Manager**: Requester's manager from HRM/org structure
- **Role**: Users with specific FA role
- **Expression**: Dynamic expression (e.g., `dept_manager($entity.department)`)

### FR-WF-005: Conditional Routing
**Requirement**: System shall support conditional branching.

**Features**:
- If-then-else conditions per step
- Field comparisons: equals, contains, greater_than, less_than
- Multiple conditions with AND/OR logic
- Skip step if condition not met
- Route to different approver based on condition

### FR-WF-006: Escalation
**Requirement**: System shall escalate overdue approvals.

**Features**:
- Configurable timeout per step
- Escalation targets: manager's manager, specific role, admin
- Escalation actions: notify, reassign, auto-approve, auto-reject
- Escalation log in audit trail
- Re-escalation option

### FR-WF-007: Post-Approval Actions
**Requirement**: System shall execute actions after approval.

**Supported Actions**:
- **Update Entity**: Set field values
- **Send Notification**: Email/SMS via EmailManager
- **Create Record**: Create related entity
- **Emit Event**: Dispatch PSR-14 event
- **Calendar Event**: Create in Calendar

### FR-WF-008: Approval Delegation
**Requirement**: System shall support approver delegation.

**Features**:
- User sets delegation for date range
- Select workflows to delegate
- Delegate receives notifications
- Original approver can still act
- Delegation logged

### FR-WF-009: Audit Trail
**Requirement**: System shall maintain complete audit log.

**Features**:
- Log all workflow events
- Record timestamps, users, actions
- Store comments at each step
- Immutable audit records
- Export audit report

### FR-WF-010: Email-Based Approval
**Requirement**: System shall support approvals via email.

**Features**:
- Send approval email with action links
- Parse reply emails
- Token-based security (7 day expiry)
- APPROVE/REJECT in subject
- Confirmation notification

## 4. Data Flows

### 4.1 Basic Approval Flow
```
Event Dispatched → Trigger Match → Instance Created → Step 1 Assigned
    → Approver Notified → Decision Made → Step 2 or Complete
```

### 4.2 Escalation Flow
```
Step Pending > Timeout → Escalate Event → New Assignee → Notify Original
```

## 5. Composer Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| ksfraser/exceptions | ^1.3 | Exception hierarchy |
| psr/event-dispatcher | ^2.0 | PSR-14 event dispatcher |
| psr/log | ^3.0 | Logging |

## 6. Exception Usage

| Exception | Extends | Description |
|-----------|---------|-------------|
| `WorkflowException` | `RuntimeException` | Base workflow exception |
| `WorkflowNotFoundException` | `WorkflowException` | Workflow not found |
| `StepNotFoundException` | `WorkflowException` | Step not found |
| `ApprovalException` | `WorkflowException` | Approval failed |

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-11*