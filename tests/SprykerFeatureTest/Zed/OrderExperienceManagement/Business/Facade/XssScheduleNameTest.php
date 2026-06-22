<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Facade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleConditionsTransfer;
use Generated\Shared\Transfer\RecurringScheduleCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group XssScheduleNameTest
 * Add your own group annotations below this line
 * @see CC-39361
 */
class XssScheduleNameTest extends Unit
{
    protected const string XSS_PAYLOAD = "<script>alert('XSS')</script>";

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
    }

    public function testScheduleWithXssNameIsStoredAndRetrievableAsRawString(): void
    {
        // Arrange — persist a schedule whose name contains a script injection payload
        $customer = $this->tester->haveCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule(
            (int)$customer->getIdCustomer(),
            [RecurringScheduleTransfer::NAME => static::XSS_PAYLOAD],
        );

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addUuid($recurringScheduleTransfer->getUuidOrFail()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert — the raw payload is returned from persistence unchanged
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());

        $retrievedName = $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getName();
        $this->assertSame(static::XSS_PAYLOAD, $retrievedName);
    }

    public function testHtmlEscapingTheStoredNameProducesSafeOutput(): void
    {
        // Arrange
        $customer = $this->tester->haveCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule(
            (int)$customer->getIdCustomer(),
            [RecurringScheduleTransfer::NAME => static::XSS_PAYLOAD],
        );

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addUuid($recurringScheduleTransfer->getUuidOrFail()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert — after applying Twig's | e equivalent, the raw <script> tag is no longer present
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());

        $retrievedName = $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getName();

        // Twig's | e filter applies htmlspecialchars with ENT_QUOTES in HTML context
        $escapedName = htmlspecialchars((string)$retrievedName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $escapedName);
        $this->assertStringNotContainsString('</script>', $escapedName);
        $this->assertStringContainsString('&lt;script&gt;', $escapedName);
    }

    public function testScheduleWithPlainNameIsUnaffectedByEscaping(): void
    {
        // Regression guard: normal names must pass through unchanged after escaping
        $plainName = 'Monthly steel coils order';
        $customer = $this->tester->haveCustomer();
        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule(
            (int)$customer->getIdCustomer(),
            [RecurringScheduleTransfer::NAME => $plainName],
        );

        $criteriaTransfer = (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addUuid($recurringScheduleTransfer->getUuidOrFail()),
            );

        // Act
        $collectionTransfer = $this->tester->getFacade()->getRecurringScheduleCollection($criteriaTransfer);

        // Assert — a safe name is identical before and after escaping
        $this->assertCount(1, $collectionTransfer->getRecurringSchedules());

        $retrievedName = $collectionTransfer->getRecurringSchedules()->offsetGet(0)->getName();
        $escapedName = htmlspecialchars((string)$retrievedName, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->assertSame($plainName, $escapedName);
    }
}
