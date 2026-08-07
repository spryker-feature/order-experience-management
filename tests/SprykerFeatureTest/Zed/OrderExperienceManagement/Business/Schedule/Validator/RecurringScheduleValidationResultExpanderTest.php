<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Validator\RecurringScheduleValidationResultExpander;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Validator
 * @group RecurringScheduleValidationResultExpanderTest
 * Add your own group annotations below this line
 */
class RecurringScheduleValidationResultExpanderTest extends Unit
{
    /**
     * Every group in getReviewReasonGroupMap() comes from a checkout error that already blocks placement, so
     * each one must mark the item non-purchasable. Consumers test `=== false`, which means a group missing
     * from getNonPurchasableReviewReasonGroups() leaves isPurchasable null and reads as purchasable.
     *
     * @dataProvider blockingReviewReasonGroupProvider
     */
    public function testExpandMarksItemNonPurchasableForEveryBlockingReviewReasonGroup(string $reviewReasonGroup): void
    {
        // Arrange
        $recurringScheduleValidationResultTransfer = (new RecurringScheduleValidationResultTransfer())
            ->addItemReview((new RecurringScheduleItemReviewTransfer())->addReviewReason($reviewReasonGroup));

        // Act
        $recurringScheduleValidationResultTransfer = $this->createExpander()->expand($recurringScheduleValidationResultTransfer);

        // Assert
        $this->assertFalse(
            $recurringScheduleValidationResultTransfer->getItemReviews()->offsetGet(0)->getIsPurchasable(),
        );
    }

    /**
     * @return array<string, array<string>>
     */
    public function blockingReviewReasonGroupProvider(): array
    {
        $reviewReasonGroups = [];

        foreach (array_keys((new OrderExperienceManagementConfig())->getReviewReasonGroupMap()) as $reviewReasonGroup) {
            $reviewReasonGroups[$reviewReasonGroup] = [$reviewReasonGroup];
        }

        return $reviewReasonGroups;
    }

    public function testExpandLeavesPurchasabilityUnsetForANonBlockingReviewReasonGroup(): void
    {
        // Arrange
        $recurringScheduleValidationResultTransfer = (new RecurringScheduleValidationResultTransfer())
            ->addItemReview(
                (new RecurringScheduleItemReviewTransfer())
                    ->addReviewReason(SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED),
            );

        // Act
        $recurringScheduleValidationResultTransfer = $this->createExpander()->expand($recurringScheduleValidationResultTransfer);

        // Assert - a price increase still needs buyer review, but the item remains purchasable.
        $this->assertNull(
            $recurringScheduleValidationResultTransfer->getItemReviews()->offsetGet(0)->getIsPurchasable(),
        );
    }

    protected function createExpander(): RecurringScheduleValidationResultExpander
    {
        return new RecurringScheduleValidationResultExpander(new OrderExperienceManagementConfig());
    }
}
