# UAT Plan - ksf_Workflow

## Document Information
- **Module**: ksf_Workflow
- **Version**: 1.0.0
- **Date**: 2026-05-11

## 1. UAT Overview

### 1.1 Purpose
Validate that ksf_Workflow correctly handles approval routing, escalation, and audit trails across all integrated modules.

### 1.2 Scope
- Workflow definition creation
- Event-driven trigger execution
- Approval routing
- Escalation and delegation
- Audit trail

## 2. UAT Scenarios

### UAT-WF-001: Leave Approval Workflow
**Scenario**: Employee submits leave request, manager approves

**Steps**:
1. As Employee, submit leave request for 3 days
2. System emits `leave.requested` event
3. Verify manager receives notification
4. As Manager, review and approve request
5. Verify status → Approved
6. Verify calendar event created
7. Verify leave balance updated

**Expected Results**:
- [ ] Workflow instance created
- [ ] Manager notified
- [ ] Approval recorded in audit
- [ ] Leave balance deducted
- [ ] Calendar event created

**Status**: ☐ Pass  ☐ Fail  ☐ N/A

---

### UAT-WF-002: Escalation on Timeout
**Scenario**: Manager doesn't respond within timeout, request escalated

**Prerequisites**: Leave request pending for > 48 hours

**Steps**:
1. Verify request pending timeout
2. System auto-escalates to manager's manager
3. Original manager receives reminder
4. Escalation logged in audit trail

**Expected Results**:
- [ ] Escalation event dispatched
- [ ] New assignee notified
- [ ] Original assignee notified
- [ ] Escalation logged

**Status**: ☐ Pass  ☐ Fail  ☐ N/A

---

### UAT-WF-003: Parallel Approval
**Scenario**: Contract requires Legal, Finance, Executive approval (all must approve)

**Steps**:
1. Submit contract for signature
2. System routes to all three simultaneously
3. Legal approves
4. Finance approves
5. Executive approves
6. Verify all approvals required for completion

**Expected Results**:
- [ ] Three approvers assigned
- [ ] Any rejection returns to author
- [ ] All approve → workflow completes

**Status**: ☐ Pass  ☐ Fail  ☐ N/A

---

### UAT-WF-004: Delegation
**Scenario**: Manager delegates approval authority during vacation

**Steps**:
1. Manager sets delegation to colleague (dates: today + 7 days)
2. Leave request arrives
3. Colleague receives notification
4. Colleague approves on behalf
5. Manager can still view but not act

**Expected Results**:
- [ ] Delegation active for date range
- [ ] Delegate receives notifications
- [ ] Approval attributed to delegate
- [ ] Original manager can view

**Status**: ☐ Pass  ☐ Fail  ☐ N/A

---

### UAT-WF-005: Email Approval
**Scenario**: Approver approves via email reply

**Steps**:
1. Approval request email received
2. Approver replies with APPROVE in subject
3. System validates email sender
4. Request approved
5. Requester receives confirmation

**Expected Results**:
- [ ] Email with action link sent
- [ ] Reply processed correctly
- [ ] Status updated
- [ ] Confirmation sent

**Status**: ☐ Pass  ☐ Fail  ☐ N/A

---

### UAT-WF-006: Audit Trail Review
**Scenario**: Compliance officer reviews approval history

**Steps**:
1. Navigate to Workflows > Audit Trail
2. Filter by date range and workflow type
3. View detailed audit entries
4. Export report (CSV/PDF)

**Expected Results**:
- [ ] All steps with timestamps visible
- [ ] User actions recorded
- [ ] Comments visible
- [ ] Export successful

**Status**: ☐ Pass  ☐ Fail  ☐ N/A

---

## 3. Sign-Off

| Role | Name | Signature | Date |
|------|------|-----------|------|
| Business Owner | | | |
| UAT Lead | | | |
| Technical Lead | | | |

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-11*