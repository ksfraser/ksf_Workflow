# Use Cases - ksf_Workflow

## UC-WF-001: Define New Workflow
**Actor**: System Administrator

**Preconditions**: User has admin role

**Flow**:
1. Navigate to Workflows > Define New
2. Enter workflow name and description
3. Define trigger event (e.g., `leave.requested`)
4. Add approval steps:
   - Step 1: Manager approval (if employee.department.manager)
   - Step 2: HR approval (if leave_type == 'sick')
   - Step 3: Finance approval (if amount > 1000)
5. Set escalation timeout (e.g., 48 hours)
6. Define post-approval actions:
   - Update record status
   - Send notification
   - Create calendar event
7. Activate workflow

**Postconditions**: Workflow is active and listening for trigger events

---

## UC-WF-002: Leave Approval Workflow
**Actor**: Manager, HR Manager, System

**Trigger**: `leave.requested` event

**Flow**:
1. Employee submits leave request (ksf_Leave)
2. System emits `leave.requested` event
3. Workflow engine loads applicable workflow
4. Step 1: Route to direct manager
   - Manager receives notification
   - Manager approves/rejects
   - If rejected → notify employee, end
5. Step 2: If leave > 5 days, route to HR
   - HR approves/rejects
   - If rejected → notify employee, end
6. Step 3: Auto-approve
7. Post-approval actions:
   - Update leave status to 'approved'
   - Deduct from leave balance
   - Create calendar event
   - Send confirmation to employee

**Alternate Flow - Escalation**:
- If manager doesn't respond within 48 hours
- Escalate to manager's manager
- Send reminder notification

**Alternate Flow - Delegation**:
- Manager has set delegation to another user
- Route to delegate instead

---

## UC-WF-003: Support Ticket Escalation
**Actor**: System, Support Manager

**Trigger**: `ticket.priority_changed` event (when priority changes to 'High')

**Flow**:
1. Ticket priority changed to 'High'
2. System emits `ticket.priority_changed` event
3. Workflow engine loads escalation workflow
4. Immediately:
   - Assign to senior support queue
   - Notify support manager
   - Create high-priority calendar event
5. If not resolved within 4 hours:
   - Escalate to director
   - Send summary email

---

## UC-WF-004: Expense Reimbursement Approval
**Actor**: Employee, Manager, Finance

**Trigger**: `expense.submitted` event

**Flow**:
1. Employee submits expense report (ksf_HRM)
2. System emits `expense.submitted` event
3. Workflow engine evaluates conditions:
   - If amount <= $100: Auto-approve, process payment
   - If amount <= $500: Manager approval only
   - If amount > $500: Manager + Finance approval
4. Send approval requests to relevant approvers
5. On approval:
   - Record approval with timestamp
   - Move to next step or complete
6. On rejection:
   - Return to employee with comments
   - Allow resubmission

---

## UC-WF-005: Document Approval Workflow
**Actor**: Document Author, Reviewer, Approver

**Trigger**: `document.submitted_for_approval` event

**Flow**:
1. Author submits document for approval (ksf_Documents)
2. System emits `document.submitted_for_approval` event
3. Route to designated reviewers (based on document type)
4. All reviewers must approve before routing to final approver
5. Final approver makes decision:
   - Approved: Publish document, version bump
   - Rejected: Return with comments
   - Request Changes: Return to author

**Postconditions**: Document status updated, version incremented if approved

---

## UC-WF-006: Parallel Approval - Contract Signing
**Actor**: Legal, Finance, Executive (parallel)

**Trigger**: `contract.ready_for_signature` event

**Flow**:
1. Contract marked ready for signature
2. System emits event
3. Parallel workflow step:
   - Notify Legal (review terms)
   - Notify Finance (review pricing)
   - Notify Executive (final approval)
4. All three must approve for workflow to continue
5. Any rejection returns to author
6. When all approve:
   - Send for e-signature
   - Execute contract
   - Update CRM opportunity to 'Contract Signed'

---

## UC-WF-007: Time-Based Escalation
**Actor**: System

**Trigger**: Cron job (daily at 9:00 AM)

**Flow**:
1. System queries all pending workflow steps
2. For each step, check:
   - Days since assigned > timeout threshold
3. If timeout exceeded:
   - Create escalation event
   - Route to escalation target (manager's manager)
   - Send reminder to original assignee
   - Log escalation in audit trail

---

## UC-WF-008: Delegation Setup
**Actor**: Any Employee

**Preconditions**: User is logged in

**Flow**:
1. Navigate to My Profile > Delegation Settings
2. Set date range for delegation
3. Select workflows to delegate
4. Choose delegate (another user)
5. Delegate receives notification
6. Delegate can accept/reject delegation

**Postconditions**: Delegation active for specified period

---

## UC-WF-009: Workflow Audit Review
**Actor**: System Administrator, Compliance Officer

**Preconditions**: User has audit role

**Flow**:
1. Navigate to Workflows > Audit Trail
2. Filter by:
   - Date range
   - Workflow type
   - Entity type
   - Status (pending, completed, rejected)
3. View audit details:
   - All steps with timestamps
   - Who approved/rejected
   - Comments at each step
   - SLA compliance
4. Export audit report (CSV/PDF)

---

## UC-WF-010: Email-Based Approval
**Actor**: Approver (via email)

**Trigger**: Approval email received

**Flow**:
1. Approver receives email with approval request
2. Email contains approve/reject buttons or links
3. Approver clicks button or replies with APPROVE/REJECT
4. System validates email is from valid approver
5. Updates workflow step accordingly
6. Sends confirmation to requester

**Security**: Email tokens expire after 7 days

*Document Version: 1.0.0*
*Last Updated: 2026-05-11*