<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Workflow\Entity\WorkflowTrigger;
use Ksfraser\Workflow\Entity\WorkflowAction;

class WorkflowTriggerTest extends TestCase
{
    public function testValidOperators(): void
    {
        $operators = WorkflowTrigger::getValidOperators();
        
        $this->assertContains('equals', $operators);
        $this->assertContains('contains', $operators);
        $this->assertContains('changes', $operators);
    }
    
    public function testValidTriggerTypes(): void
    {
        $types = WorkflowTrigger::getValidTriggerTypes();
        
        $this->assertContains('on_save', $types);
        $this->assertContains('on_create', $types);
        $this->assertContains('on_update', $types);
    }
    
    public function testValidEntityTypes(): void
    {
        $types = WorkflowTrigger::getValidEntityTypes();
        
        $this->assertContains('debtor', $types);
        $this->assertContains('ticket', $types);
        $this->assertContains('opportunity', $types);
    }
    
    public function testEvaluateEquals(): void
    {
        $trigger = new WorkflowTrigger();
        $trigger->fieldName = 'status';
        $trigger->operator = 'equals';
        $trigger->fieldValue = 'Closed';
        $trigger->isActive = true;
        
        $entity = ['status' => 'Closed'];
        $result = $trigger->evaluate($entity);
        
        $this->assertTrue($result);
    }
    
    public function testEvaluateNotEquals(): void
    {
        $trigger = new WorkflowTrigger();
        $trigger->fieldName = 'status';
        $trigger->operator = 'not_equals';
        $trigger->fieldValue = 'Closed';
        
        $entity = ['status' => 'New'];
        $result = $trigger->evaluate($entity);
        
        $this->assertTrue($result);
    }
    
    public function testEvaluateContains(): void
    {
        $trigger = new WorkflowTrigger();
        $trigger->fieldName = 'subject';
        $trigger->operator = 'contains';
        $trigger->fieldValue = 'urgent';
        
        $entity = ['subject' => 'This is an urgent matter'];
        $result = $trigger->evaluate($entity);
        
        $this->assertTrue($result);
    }
    
    public function testEvaluateChanges(): void
    {
        $trigger = new WorkflowTrigger();
        $trigger->fieldName = 'status';
        $trigger->operator = 'changes';
        
        $entity = ['status' => 'Closed'];
        $oldEntity = ['status' => 'New'];
        $result = $trigger->evaluate($entity, $oldEntity);
        
        $this->assertTrue($result);
    }
    
    public function testEvaluateChangesFrom(): void
    {
        $trigger = new WorkflowTrigger();
        $trigger->fieldName = 'status';
        $trigger->operator = 'changes_from';
        $trigger->fieldValue = 'New';
        
        $entity = ['status' => 'InProgress'];
        $oldEntity = ['status' => 'New'];
        $result = $trigger->evaluate($entity, $oldEntity);
        
        $this->assertTrue($result);
    }
    
    public function testIsEmpty(): void
    {
        $trigger = new WorkflowTrigger();
        $trigger->fieldName = 'email';
        $trigger->operator = 'is_empty';
        
        $entity = ['email' => ''];
        $result = $trigger->evaluate($entity);
        
        $this->assertTrue($result);
    }
    
    public function testInactiveTrigger(): void
    {
        $trigger = new WorkflowTrigger();
        $trigger->fieldName = 'status';
        $trigger->operator = 'equals';
        $trigger->fieldValue = 'Closed';
        $trigger->isActive = false;
        
        $entity = ['status' => 'Closed'];
        $result = $trigger->evaluate($entity);
        
        $this->assertFalse($result);
    }
    
    public function testToArray(): void
    {
        $trigger = new WorkflowTrigger();
        $trigger->id = 1;
        $trigger->name = 'Close Ticket';
        $trigger->entityType = 'ticket';
        $trigger->fieldName = 'status';
        $trigger->operator = 'equals';
        $trigger->fieldValue = 'Closed';
        $trigger->triggerType = 'on_save';
        $trigger->isActive = true;
        $trigger->priority = 10;
        
        $arr = $trigger->toArray();
        
        $this->assertEquals(1, $arr['id']);
        $this->assertEquals('ticket', $arr['entity_type']);
        $this->assertEquals('equals', $arr['operator']);
    }
    
    public function testFromArray(): void
    {
        $data = [
            'id' => 5,
            'name' => 'Test Trigger',
            'entity_type' => 'debtor',
            'field_name' => 'credit_limit',
            'operator' => 'greater_than',
            'field_value' => '1000',
            'trigger_type' => 'on_update',
            'is_active' => true,
            'priority' => 5,
        ];
        
        $trigger = WorkflowTrigger::fromArray($data);
        
        $this->assertEquals(5, $trigger->id);
        $this->assertEquals('debtor', $trigger->entityType);
        $this->assertEquals('greater_than', $trigger->operator);
    }
}