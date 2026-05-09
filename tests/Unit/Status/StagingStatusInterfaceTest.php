<?php

declare(strict_types=1);

namespace Ksfraser\Workflow\Tests\Unit\Status;

use Ksfraser\Workflow\Status\StagingStatusInterface;
use Ksfraser\Workflow\Status\StagingStatusTrait;
use PHPUnit\Framework\TestCase;

class StagingStatusTest implements StagingStatusInterface
{
    use StagingStatusTrait;
}

class StagingStatusInterfaceTest extends TestCase
{
    public function testGetStagingStatuses(): void
    {
        $statuses = StagingStatusTest::getStagingStatuses();
        
        $this->assertContains(StagingStatusInterface::STATUS_STAGED, $statuses);
        $this->assertContains(StagingStatusInterface::STATUS_PENDING_REVIEW, $statuses);
        $this->assertContains(StagingStatusInterface::STATUS_MATCHED, $statuses);
        $this->assertContains(StagingStatusInterface::STATUS_PROCESSING, $statuses);
        $this->assertContains(StagingStatusInterface::STATUS_PROCESSED, $statuses);
        $this->assertContains(StagingStatusInterface::STATUS_IMPORTED, $statuses);
    }

    public function testGetStagingStatusDescription(): void
    {
        $this->assertStringContainsString('Staged', StagingStatusTest::getStagingStatusDescription(StagingStatusInterface::STATUS_STAGED));
        $this->assertStringContainsString('Matched', StagingStatusTest::getStagingStatusDescription(StagingStatusInterface::STATUS_MATCHED));
        $this->assertStringContainsString('Imported', StagingStatusTest::getStagingStatusDescription(StagingStatusInterface::STATUS_IMPORTED));
    }

    public function testIsImportable(): void
    {
        $this->assertTrue(StagingStatusTest::isImportable(StagingStatusInterface::STATUS_MATCHED));
        $this->assertTrue(StagingStatusTest::isImportable(StagingStatusInterface::STATUS_PROCESSING));
        $this->assertTrue(StagingStatusTest::isImportable(StagingStatusInterface::STATUS_PROCESSED));
        $this->assertTrue(StagingStatusTest::isImportable(StagingStatusInterface::STATUS_IMPORTED));
        
        $this->assertFalse(StagingStatusTest::isImportable(StagingStatusInterface::STATUS_STAGED));
        $this->assertFalse(StagingStatusTest::isImportable(StagingStatusInterface::STATUS_PENDING_REVIEW));
    }

    public function testRequiresAction(): void
    {
        $this->assertTrue(StagingStatusTest::requiresAction(StagingStatusInterface::STATUS_STAGED));
        $this->assertTrue(StagingStatusTest::requiresAction(StagingStatusInterface::STATUS_PENDING_REVIEW));
        $this->assertTrue(StagingStatusTest::requiresAction(StagingStatusInterface::STATUS_ERROR));
        
        $this->assertFalse(StagingStatusTest::requiresAction(StagingStatusInterface::STATUS_IMPORTED));
        $this->assertFalse(StagingStatusTest::requiresAction(StagingStatusInterface::STATUS_MATCHED));
    }

    public function testGetInitialStagingStatus(): void
    {
        $this->assertEquals(StagingStatusInterface::STATUS_STAGED, StagingStatusTest::getInitialStagingStatus());
    }

    public function testGetFinalStagingStatus(): void
    {
        $this->assertEquals(StagingStatusInterface::STATUS_IMPORTED, StagingStatusTest::getFinalStagingStatus());
    }

    public function testInheritsFromWorkflowStatus(): void
    {
        $this->assertContains(StagingStatusInterface::STATUS_PENDING, StagingStatusTest::getStagingStatuses());
        $this->assertContains(StagingStatusInterface::STATUS_COMPLETED, StagingStatusTest::getStagingStatuses());
    }
}