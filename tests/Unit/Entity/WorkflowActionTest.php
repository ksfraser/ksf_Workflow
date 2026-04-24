<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Workflow\Entity\WorkflowAction;

class WorkflowActionTest extends TestCase
{
    public function testValidActionTypes(): void
    {
        $types = WorkflowAction::getValidActionTypes();
        
        $this->assertContains('update_field', $types);
        $this->assertContains('calculate', $types);
        $this->assertContains('trigger_event', $types);
        $this->assertContains('send_email', $types);
    }
    
    public function testExecuteUpdateField(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'update_field';
        $action->actionConfig = json_encode(['field' => 'status', 'value' => 'Closed']);
        $action->isActive = true;
        
        $entity = ['status' => 'New'];
        $result = $action->execute($entity);
        
        $this->assertTrue($result);
        $this->assertEquals('Closed', $entity['status']);
    }
    
    public function testExecuteSetField(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'set_field';
        $action->actionConfig = json_encode(['field' => 'assigned_to', 'value' => 'user1']);
        $action->isActive = true;
        
        $entity = [];
        $result = $action->execute($entity);
        
        $this->assertTrue($result);
        $this->assertEquals('user1', $entity['assigned_to']);
    }
    
    public function testExecuteCalculate(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'calculate';
        $action->actionConfig = json_encode([
            'target_field' => 'total',
            'expression' => '{quantity} * {unit_price}',
        ]);
        $action->isActive = true;
        
        $entity = ['quantity' => 5, 'unit_price' => 10];
        $result = $action->execute($entity);
        
        $this->assertTrue($result);
        $this->assertEquals(50, $entity['total']);
    }
    
    public function testExecuteAssignTo(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'assign_to';
        $action->actionConfig = json_encode(['user_id' => 'admin']);
        $action->isActive = true;
        
        $entity = [];
        $result = $action->execute($entity);
        
        $this->assertTrue($result);
        $this->assertEquals('admin', $entity['assigned_to']);
    }
    
    public function testInactiveAction(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'update_field';
        $action->actionConfig = json_encode(['field' => 'status']);
        $action->isActive = false;
        
        $entity = ['status' => 'New'];
        $result = $action->execute($entity);
        
        $this->assertFalse($result);
    }
    
    public function testBuiltInFunctions(): void
    {
        $funcs = WorkflowAction::getBuiltInFunctions();
        
        $this->assertContains('sum', $funcs);
        $this->assertContains('avg', $funcs);
        $this->assertContains('round', $funcs);
    }
    
    public function testToArray(): void
    {
        $action = new WorkflowAction();
        $action->id = 1;
        $action->triggerId = 5;
        $action->name = 'Close Ticket';
        $action->actionType = 'update_field';
        $action->actionConfig = json_encode(['field' => 'status', 'value' => 'Closed']);
        $action->order = 1;
        $action->isActive = true;
        
        $arr = $action->toArray();
        
        $this->assertEquals(1, $arr['id']);
        $this->assertEquals(5, $arr['trigger_id']);
        $this->assertEquals('update_field', $arr['action_type']);
    }
}