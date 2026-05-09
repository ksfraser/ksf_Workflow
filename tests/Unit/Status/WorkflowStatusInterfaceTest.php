<?php

declare(strict_types=1);

namespace Ksfraser\Workflow\Tests\Unit\Status;

use Ksfraser\Workflow\Status\WorkflowStatusInterface;
use Ksfraser\Workflow\Status\WorkflowStatusTrait;
use PHPUnit\Framework\TestCase;

class WorkflowStatusTest implements WorkflowStatusInterface
{
    use WorkflowStatusTrait;
}

class WorkflowStatusInterfaceTest extends TestCase
{
    public function testGetStatuses(): void
    {
        $statuses = WorkflowStatusTest::getStatuses();
        
        $this->assertContains(WorkflowStatusInterface::STATUS_PENDING, $statuses);
        $this->assertContains(WorkflowStatusInterface::STATUS_IN_PROGRESS, $statuses);
        $this->assertContains(WorkflowStatusInterface::STATUS_COMPLETED, $statuses);
        $this->assertContains(WorkflowStatusInterface::STATUS_ERROR, $statuses);
        $this->assertContains(WorkflowStatusInterface::STATUS_FAILED, $statuses);
        $this->assertContains(WorkflowStatusInterface::STATUS_CANCELLED, $statuses);
    }

    public function testGetStatusDescription(): void
    {
        $this->assertStringContainsString('Pending', WorkflowStatusTest::getStatusDescription(WorkflowStatusInterface::STATUS_PENDING));
        $this->assertStringContainsString('In Progress', WorkflowStatusTest::getStatusDescription(WorkflowStatusInterface::STATUS_IN_PROGRESS));
        $this->assertStringContainsString('Completed', WorkflowStatusTest::getStatusDescription(WorkflowStatusInterface::STATUS_COMPLETED));
    }

    public function testIsFinalStatus(): void
    {
        $this->assertTrue(WorkflowStatusTest::isFinalStatus(WorkflowStatusInterface::STATUS_COMPLETED));
        $this->assertTrue(WorkflowStatusTest::isFinalStatus(WorkflowStatusInterface::STATUS_FAILED));
        $this->assertTrue(WorkflowStatusTest::isFinalStatus(WorkflowStatusInterface::STATUS_CANCELLED));
        $this->assertFalse(WorkflowStatusTest::isFinalStatus(WorkflowStatusInterface::STATUS_PENDING));
        $this->assertFalse(WorkflowStatusTest::isFinalStatus(WorkflowStatusInterface::STATUS_IN_PROGRESS));
    }

    public function testIsErrorStatus(): void
    {
        $this->assertTrue(WorkflowStatusTest::isErrorStatus(WorkflowStatusInterface::STATUS_ERROR));
        $this->assertTrue(WorkflowStatusTest::isErrorStatus(WorkflowStatusInterface::STATUS_FAILED));
        $this->assertFalse(WorkflowStatusTest::isErrorStatus(WorkflowStatusInterface::STATUS_PENDING));
    }

    public function testCanRetry(): void
    {
        $this->assertTrue(WorkflowStatusTest::canRetry(WorkflowStatusInterface::STATUS_ERROR));
        $this->assertTrue(WorkflowStatusTest::canRetry(WorkflowStatusInterface::STATUS_FAILED));
        $this->assertFalse(WorkflowStatusTest::canRetry(WorkflowStatusInterface::STATUS_PENDING));
        $this->assertFalse(WorkflowStatusTest::canRetry(WorkflowStatusInterface::STATUS_COMPLETED));
    }

    public function testIsActiveStatus(): void
    {
        $this->assertTrue(WorkflowStatusTest::isActiveStatus(WorkflowStatusInterface::STATUS_PENDING));
        $this->assertTrue(WorkflowStatusTest::isActiveStatus(WorkflowStatusInterface::STATUS_IN_PROGRESS));
        $this->assertFalse(WorkflowStatusTest::isActiveStatus(WorkflowStatusInterface::STATUS_COMPLETED));
    }

    public function testUnknownStatusDescription(): void
    {
        $desc = WorkflowStatusTest::getStatusDescription('unknown_status');
        $this->assertStringContainsString('Unknown status', $desc);
    }
}