<?php

declare(strict_types=1);

namespace Ksfraser\Workflow\Tests\Unit\StateMachine;

use Ksfraser\Workflow\StateMachine\StagingStateMachine;
use Ksfraser\Workflow\Status\StagingStatusInterface;
use PHPUnit\Framework\TestCase;

class StagingStateMachineTest extends TestCase
{
    private StagingStateMachine $stateMachine;

    protected function setUp(): void
    {
        $this->stateMachine = new StagingStateMachine();
    }

    public function testCanTransitionFromStagedToMatched(): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition(
                StagingStatusInterface::STATUS_STAGED,
                StagingStatusInterface::STATUS_MATCHED
            )
        );
    }

    public function testCannotTransitionFromStagedToImported(): void
    {
        $this->assertFalse(
            $this->stateMachine->canTransition(
                StagingStatusInterface::STATUS_STAGED,
                StagingStatusInterface::STATUS_IMPORTED
            )
        );
    }

    public function testGetValidTransitions(): void
    {
        $transitions = $this->stateMachine->getValidTransitions(StagingStatusInterface::STATUS_STAGED);
        
        $this->assertContains(StagingStatusInterface::STATUS_PENDING_REVIEW, $transitions);
        $this->assertContains(StagingStatusInterface::STATUS_MATCHED, $transitions);
        $this->assertContains(StagingStatusInterface::STATUS_IN_PROGRESS, $transitions);
        $this->assertContains(StagingStatusInterface::STATUS_ERROR, $transitions);
    }

    public function testTransitionRecordsHistory(): void
    {
        $result = $this->stateMachine->transition(
            StagingStatusInterface::STATUS_STAGED,
            StagingStatusInterface::STATUS_MATCHED,
            ['woo_customer_id' => 123]
        );

        $this->assertTrue($result);
        
        $history = $this->stateMachine->getTransitionHistory();
        $this->assertCount(1, $history);
        $this->assertEquals(StagingStatusInterface::STATUS_STAGED, $history[0]['from_status']);
        $this->assertEquals(StagingStatusInterface::STATUS_MATCHED, $history[0]['to_status']);
    }

    public function testInvalidTransitionSetsError(): void
    {
        $result = $this->stateMachine->transition(
            StagingStatusInterface::STATUS_IMPORTED,
            StagingStatusInterface::STATUS_STAGED
        );

        $this->assertFalse($result);
        $this->assertNotNull($this->stateMachine->getLastError());
    }

    public function testIsFinalState(): void
    {
        $this->assertTrue($this->stateMachine->isFinalState(StagingStatusInterface::STATUS_IMPORTED));
        $this->assertFalse($this->stateMachine->isFinalState(StagingStatusInterface::STATUS_STAGED));
    }

    public function testImportedHasNoTransitions(): void
    {
        $transitions = $this->stateMachine->getValidTransitions(StagingStatusInterface::STATUS_IMPORTED);
        $this->assertEmpty($transitions);
    }

    public function testClearHistory(): void
    {
        $this->stateMachine->transition(StagingStatusInterface::STATUS_STAGED, StagingStatusInterface::STATUS_MATCHED);
        $this->assertCount(1, $this->stateMachine->getTransitionHistory());
        
        $this->stateMachine->clearHistory();
        $this->assertEmpty($this->stateMachine->getTransitionHistory());
    }

    public function testAddTransition(): void
    {
        $this->stateMachine->addTransition(StagingStatusInterface::STATUS_IMPORTED, StagingStatusInterface::STATUS_STAGED);
        
        $this->assertTrue(
            $this->stateMachine->canTransition(
                StagingStatusInterface::STATUS_IMPORTED,
                StagingStatusInterface::STATUS_STAGED
            )
        );
    }

    public function testRemoveTransition(): void
    {
        $this->stateMachine->removeTransition(StagingStatusInterface::STATUS_STAGED, StagingStatusInterface::STATUS_ERROR);
        
        $this->assertFalse(
            $this->stateMachine->canTransition(
                StagingStatusInterface::STATUS_STAGED,
                StagingStatusInterface::STATUS_ERROR
            )
        );
    }

    public function testFullWorkflow(): void
    {
        $this->assertTrue($this->stateMachine->transition(StagingStatusInterface::STATUS_STAGED, StagingStatusInterface::STATUS_MATCHED));
        $this->assertTrue($this->stateMachine->transition(StagingStatusInterface::STATUS_MATCHED, StagingStatusInterface::STATUS_IN_PROGRESS));
        $this->assertTrue($this->stateMachine->transition(StagingStatusInterface::STATUS_IN_PROGRESS, StagingStatusInterface::STATUS_COMPLETED));
        $this->assertTrue($this->stateMachine->transition(StagingStatusInterface::STATUS_COMPLETED, StagingStatusInterface::STATUS_IMPORTED));
        
        $this->assertCount(4, $this->stateMachine->getTransitionHistory());
    }

    public function testErrorCanRetry(): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition(
                StagingStatusInterface::STATUS_ERROR,
                StagingStatusInterface::STATUS_STAGED
            )
        );
    }
}