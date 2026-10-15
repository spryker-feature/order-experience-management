<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Form\DataProvider;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleOrderReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider\RecurringOrderApproveFormDataProvider;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderAcceptedItemForm;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringOrderApproveForm;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Form
 * @group DataProvider
 * @group RecurringOrderApproveFormDataProviderTest
 */
class RecurringOrderApproveFormDataProviderTest extends Unit
{
    protected const string UUID = 'schedule-uuid';

    public function testSeedsNothingForAReviewWithoutFlaggedItemsOrOrderReview(): void
    {
        // Act
        $data = $this->getData(new RecurringScheduleReviewResponseTransfer());

        // Assert
        $this->assertSame([], $data[RecurringOrderApproveForm::FIELD_ACCEPTED_ITEMS]);
    }

    public function testSeedsTheCurrentPriceOfEveryItemFlaggedByTheOrderTotal(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())
            ->setOrderReview(new RecurringScheduleOrderReviewTransfer())
            ->addFlaggedItem($this->createFlaggedItem('gk-a', 510))
            ->addFlaggedItem($this->createFlaggedItem('gk-b', 320));

        // Act
        $acceptedItems = $this->getData($recurringScheduleReviewResponseTransfer)[RecurringOrderApproveForm::FIELD_ACCEPTED_ITEMS];

        // Assert
        $this->assertSame([
            [RecurringOrderAcceptedItemForm::FIELD_GROUP_KEY => 'gk-a', RecurringOrderAcceptedItemForm::FIELD_PRICE => 510],
            [RecurringOrderAcceptedItemForm::FIELD_GROUP_KEY => 'gk-b', RecurringOrderAcceptedItemForm::FIELD_PRICE => 320],
        ], $acceptedItems);
    }

    public function testSkipsAFlaggedItemWithoutAGroupKey(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = (new RecurringScheduleReviewResponseTransfer())->addFlaggedItem(
            (new RecurringScheduleItemReviewTransfer())
                ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setBundleItemIdentifier('bundle-1'))
                ->setCurrentPrice(100)
                ->setIsFlaggedByOrderTotal(true),
        );

        // Act
        $acceptedItems = $this->getData($recurringScheduleReviewResponseTransfer)[RecurringOrderApproveForm::FIELD_ACCEPTED_ITEMS];

        // Assert
        $this->assertSame([], $acceptedItems);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer): array
    {
        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getSubstitutableReviewReasons')->willReturn(['discontinued', 'substituted']);

        return (new RecurringOrderApproveFormDataProvider($configMock))->getData(static::UUID, $recurringScheduleReviewResponseTransfer);
    }

    protected function createFlaggedItem(string $groupKey, int $currentPrice): RecurringScheduleItemReviewTransfer
    {
        return (new RecurringScheduleItemReviewTransfer())
            ->setRecurringScheduleItem((new RecurringScheduleItemTransfer())->setGroupKey($groupKey))
            ->setCurrentPrice($currentPrice)
            ->setIsFlaggedByOrderTotal(true);
    }
}
