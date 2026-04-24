<?php

use PHPUnit\Framework\TestCase;
use Ksfraser\Workflow\Entity\WorkflowAction;

class WorkflowChainTest extends TestCase
{
    public function testChainedActionsLoadRecord(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'load_record';
        $action->actionConfig = json_encode([
            'entity_type' => 'debtor',
            'entity_id' => 5,
            'target_field' => 'customer',
        ]);
        $action->isActive = true;
        
        $entity = [];
        $result = $action->execute($entity);
        
        $this->assertTrue($result);
        $this->assertArrayHasKey('customer', $entity);
        $this->assertEquals('debtor', $entity['customer']['type']);
        $this->assertEquals(5, $entity['customer']['id']);
    }
    
    public function testChainedActionsLoadRelated(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'load_related';
        $action->actionConfig = json_encode([
            'relation' => 'contact',
            'source_field' => 'contact_id',
            'target_field' => 'contact',
        ]);
        $action->isActive = true;
        
        $entity = ['contact_id' => 10];
        $result = $action->execute($entity);
        
        $this->assertTrue($result);
        $this->assertArrayHasKey('contact', $entity);
        $this->assertEquals(10, $entity['contact']['id']);
    }
    
    public function testChainedActionsExecuteChain(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'chain';
        $action->actionConfig = json_encode([
            'actions' => [
                ['type' => 'update_field', 'field' => 'status', 'value' => 'InProgress'],
                ['type' => 'calculate', 'target_field' => 'total', 'expression' => '{quantity} * {unit_price}'],
                ['type' => 'set_field', 'field' => 'assigned_to', 'value' => 'admin'],
            ]
        ]);
        $action->isActive = true;
        
        $entity = ['quantity' => 5, 'unit_price' => 10];
        $result = $action->execute($entity);
        
        $this->assertTrue($result);
        $this->assertEquals('InProgress', $entity['status']);
        $this->assertEquals(50, $entity['total']);
        $this->assertEquals('admin', $entity['assigned_to']);
    }
    
    public function testChainWithLoadRecord(): void
    {
        $action = new WorkflowAction();
        $action->actionType = 'chain';
        $action->actionConfig = json_encode([
            'actions' => [
                ['type' => 'update_field', 'field' => 'step', 'value' => 1],
                ['type' => 'load_record', 'entity_type' => 'debtor', 'entity_id' => 10, 'target_field' => 'customer'],
                ['type' => 'calculate', 'target_field' => 'total', 'expression' => '{amount} * 1.1'],
            ]
        ]);
        $action->isActive = true;
        
        $entity = ['amount' => 100];
        $result = $action->execute($entity);
        
        $this->assertTrue($result);
        $this->assertEquals(1, $entity['step']);
        $this->assertArrayHasKey('customer', $entity);
        $this->assertEqualsWithDelta(110, $entity['total'], 0.01);
    }
}