# Test Plan - ksf_Workflow

## Document Information
- **Module**: ksf_Workflow
- **Version**: 1.0.0
- **Date**: 2026-05-11
- **Test Framework**: PHPUnit 10.x

## 1. Test Strategy

### 1.1 Coverage Target
- 100% line/branch coverage on production code
- Exception classes excluded (no logic)

### 1.2 Test Categories
- **Unit Tests**: Service, Entity, Repository
- **Integration Tests**: Event dispatch, workflow execution
- All dependencies mocked (PSR interfaces)

## 2. Test Structure

```
ksf_Workflow/tests/
├── bootstrap.php
└── Unit/
    ├── WorkflowServiceTest.php
    ├── Entity/
    │   ├── WorkflowDefinitionTest.php
    │   ├── WorkflowInstanceTest.php
    │   └── WorkflowStepInstanceTest.php
    ├── ApproverResolverTest.php
    └── Repository/
        └── WorkflowRepositoryTest.php
```

## 3. Test Cases

### 3.1 WorkflowServiceTest

| Test | Description | Expected Result |
|------|-------------|-----------------|
| testCreateWorkflowDefinition | Create valid definition | Definition saved |
| testLoadWorkflowByTriggerEvent | Load workflow for event | Correct definition returned |
| testStartWorkflow | Start workflow instance | Instance created, event emitted |
| testResolveApprover | Resolve manager approver | Correct user returned |
| testRouteToNextStep | Route after approval | Next step assigned |
| testEscalateOnTimeout | Escalate overdue step | Escalation event, new assignee |
| testCompleteWorkflow | All steps approved | Status = completed |
| testRejectWorkflow | Step rejected | Status = rejected, event emitted |
| testDelegationHandling | Delegate receives approvals | Correct assignee |
| testInvalidWorkflowThrows | Invalid definition | ValidationException |

### 3.2 WorkflowDefinitionTest

| Test | Description |
|------|-------------|
| testCreateWithValidData | Valid definition created |
| testAddStep | Step added to definition |
| testReorderSteps | Steps reordered |
| testSerializeToJson | JSON serialization |
| testDeserializeFromJson | JSON deserialization |
| testActivateWorkflow | Workflow activated |
| testCloneWorkflow | Workflow cloned |

### 3.3 WorkflowInstanceTest

| Test | Description |
|------|-------------|
| testCreateInstance | Instance created from definition |
| testAdvanceToStep | Current step advanced |
| testCompleteInstance | Status = completed |
| testCancelInstance | Status = cancelled |
| testGetPendingSteps | Returns pending steps |
| testGetCompletedSteps | Returns completed steps |

### 3.4 ApproverResolverTest

| Test | Description |
|------|-------------|
| testResolveDirectUser | Specific user returned |
| testResolveManager | Manager from HRM returned |
| testResolveRole | Users with role returned |
| testResolveDelegation | Delegate returned |
| testNoApproverFound | Exception thrown |
| testCircularDelegationPrevention | Exception thrown |

## 4. Mock Strategy

| Interface | Mock Type |
|-----------|-----------|
| WorkflowRepositoryInterface | Mock |
| ApproverResolverInterface | Mock |
| EventDispatcherInterface | Mock |
| LoggerInterface | Mock |
| EmployeeServiceInterface | Mock |

## 5. Test Data

### 5.1 Sample Workflow Definition
```json
{
    "name": "Leave Approval",
    "trigger_event": "leave.requested",
    "steps": [
        {
            "step_order": 1,
            "step_type": "approval",
            "approver_type": "manager"
        },
        {
            "step_order": 2,
            "step_type": "condition",
            "conditions": [
                {"field": "days", "operator": ">", "value": 5}
            ]
        }
    ]
}
```

### 5.2 Sample User
- User ID: `user-001`
- Manager ID: `user-002`
- Role: `manager`

## 6. CI/CD Integration

```yaml
# .github/workflows/test.yml
- name: Run PHPUnit
  run: ./vendor/bin/phpunit --coverage-clover coverage.xml
```

## 7. Quality Gates

- [ ] All unit tests pass
- [ ] Code coverage ≥ 80%
- [ ] phpstan level 8 passes
- [ ] phpcs passes PSR-12

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-11*