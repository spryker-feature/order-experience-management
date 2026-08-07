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
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItemQuery;
use Spryker\Zed\CompanyRole\Communication\Plugin\PermissionStoragePlugin;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeatureTest\Zed\OrderExperienceManagement\OrderExperienceManagementBusinessTester;
use SprykerFeatureTest\Zed\OrderExperienceManagement\Stub\FixedBlockingErrorScheduleValidatorPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Facade
 * @group GetRecurringScheduleReviewTest
 * Add your own group annotations below this line
 */
class GetRecurringScheduleReviewTest extends Unit
{
    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     */
    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    /**
     * @uses \SprykerFeatureTest\Shared\OrderExperienceManagement\Helper\RecurringScheduleHelper::OVERRIDE_BUILD_QUOTE_DATA
     */
    protected const string OVERRIDE_BUILD_QUOTE_DATA = 'build_quote_data';

    /**
     * @uses \SprykerFeatureTest\Shared\OrderExperienceManagement\Helper\RecurringScheduleHelper::OVERRIDE_BUILD_ITEM_DATA
     */
    protected const string OVERRIDE_BUILD_ITEM_DATA = 'build_item_data';

    protected const string SKU_A = 'sku-a';

    protected const string SKU_B = 'sku-b';

    protected const string CONFIGURED_BUNDLE_GROUP_KEY = 'cb-group-1';

    protected const string BLOCKING_ERROR_MESSAGE = 'recurring_orders.review.blocking_error';

    protected OrderExperienceManagementBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->ensureRecurringScheduleTablesAreEmpty();
        $this->tester->disableScheduleValidatorPlugins();
        $this->tester->pinMailFacadeDependency();
    }

    public function testReturnsReviewWithAllItemsUnchangedWhenNothingIsFlagged(): void
    {
        // Arrange
        $uuid = $this->haveScheduleWithItems([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 2, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300],
        ]);

        // Act
        $recurringScheduleReviewResponseTransfer = $this->tester->getFacade()->getRecurringScheduleReview($this->createReviewCriteria($uuid));

        // Assert
        $this->assertNotNull($recurringScheduleReviewResponseTransfer->getRecurringSchedule());
        $this->assertCount(0, $recurringScheduleReviewResponseTransfer->getFlaggedItems());
        $this->assertCount(2, $recurringScheduleReviewResponseTransfer->getUnchangedItems());
        $this->assertSame(0, $recurringScheduleReviewResponseTransfer->getRemovedItemCount());
        $this->assertSame(0, $recurringScheduleReviewResponseTransfer->getPriceChangeCount());
        $this->assertSame(1300, $recurringScheduleReviewResponseTransfer->getOriginalTotal());
        $this->assertSame(1300, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testReturnsEmptyResponseWhenNoScheduleMatchesCriteria(): void
    {
        // Act
        $recurringScheduleReviewResponseTransfer = $this->tester->getFacade()->getRecurringScheduleReview(
            $this->createReviewCriteria('non-existent-uuid'),
        );

        // Assert
        $this->assertNull($recurringScheduleReviewResponseTransfer->getRecurringSchedule());
        $this->assertCount(0, $recurringScheduleReviewResponseTransfer->getFlaggedItems());
        $this->assertCount(0, $recurringScheduleReviewResponseTransfer->getUnchangedItems());
    }

    public function testFlagsPriceIncreasedItemAndCountsPriceChange(): void
    {
        // Arrange
        $uuid = $this->haveScheduleWithItems([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 2, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300],
        ]);

        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, true, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED, 800),
        ]);

        // Act
        $recurringScheduleReviewResponseTransfer = $this->tester->getFacade()->getRecurringScheduleReview($this->createReviewCriteria($uuid));

        // Assert
        $this->assertCount(1, $recurringScheduleReviewResponseTransfer->getFlaggedItems());
        $this->assertCount(1, $recurringScheduleReviewResponseTransfer->getUnchangedItems());
        $this->assertSame(1, $recurringScheduleReviewResponseTransfer->getPriceChangeCount());
        $this->assertSame(0, $recurringScheduleReviewResponseTransfer->getRemovedItemCount());

        $flaggedItemReviewTransfer = $recurringScheduleReviewResponseTransfer->getFlaggedItems()->offsetGet(0);
        $this->assertSame(static::SKU_A, $flaggedItemReviewTransfer->getRecurringScheduleItemOrFail()->getSku());
        $this->assertSame(800, $flaggedItemReviewTransfer->getCurrentPrice());
        $this->assertSame(1900, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testFlagsUnpurchasableItemAndExcludesItFromUpdatedTotal(): void
    {
        // Arrange
        $uuid = $this->haveScheduleWithItems([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 2, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
            [RecurringScheduleItemTransfer::SKU => static::SKU_B, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 300],
        ]);

        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, false, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE),
        ]);

        // Act
        $recurringScheduleReviewResponseTransfer = $this->tester->getFacade()->getRecurringScheduleReview($this->createReviewCriteria($uuid));

        // Assert
        $this->assertCount(1, $recurringScheduleReviewResponseTransfer->getFlaggedItems());
        $this->assertSame(1, $recurringScheduleReviewResponseTransfer->getRemovedItemCount());
        $this->assertSame(1, $recurringScheduleReviewResponseTransfer->getUnavailableCount());
        $this->assertSame(300, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testFlagsAllConfigurableBundleMembersWhenOneMemberIsUnpurchasable(): void
    {
        // Arrange
        $uuid = $this->haveScheduleWithItems([
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_A,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
                RecurringScheduleItemTransfer::CONFIGURED_BUNDLE_GROUP_KEY => static::CONFIGURED_BUNDLE_GROUP_KEY,
            ],
            [
                RecurringScheduleItemTransfer::SKU => static::SKU_B,
                RecurringScheduleItemTransfer::QUANTITY => 1,
                RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 400,
                RecurringScheduleItemTransfer::CONFIGURED_BUNDLE_GROUP_KEY => static::CONFIGURED_BUNDLE_GROUP_KEY,
            ],
            [RecurringScheduleItemTransfer::SKU => 'sku-standalone', RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 100],
        ]);

        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, false, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE),
        ]);

        // Act
        $recurringScheduleReviewResponseTransfer = $this->tester->getFacade()->getRecurringScheduleReview($this->createReviewCriteria($uuid));

        // Assert
        $this->assertCount(2, $recurringScheduleReviewResponseTransfer->getFlaggedItems());
        $this->assertCount(1, $recurringScheduleReviewResponseTransfer->getUnchangedItems());
        $this->assertSame(2, $recurringScheduleReviewResponseTransfer->getRemovedItemCount());

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $flaggedItemReviewTransfer) {
            $this->assertFalse($flaggedItemReviewTransfer->getIsPurchasable());
            $this->assertContains(
                SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_CONFIGURABLE_BUNDLE_UNAVAILABLE,
                $flaggedItemReviewTransfer->getReviewReasons(),
            );
        }

        $this->assertSame(100, $recurringScheduleReviewResponseTransfer->getUpdatedTotal());
    }

    public function testReturnsEmptyReviewWhenCustomerDoesNotOwnSchedule(): void
    {
        // Arrange
        $this->tester->preparePermissionStorageDependency(new PermissionStoragePlugin());

        $ownerCustomerTransfer = $this->tester->haveCustomer();
        $otherCustomerTransfer = $this->tester->haveCustomer();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$ownerCustomerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
        ]);
        $this->tester->haveRecurringScheduleItem($recurringScheduleTransfer->getIdRecurringScheduleOrFail(), [
            RecurringScheduleItemTransfer::SKU => static::SKU_A,
            RecurringScheduleItemTransfer::QUANTITY => 1,
            RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500,
        ]);

        // Review requested by a different customer — ownership scoping must exclude the schedule.
        $criteriaTransfer = $this->createReviewCriteria($recurringScheduleTransfer->getUuidOrFail())
            ->setCustomer($otherCustomerTransfer);

        // Act
        $recurringScheduleReviewResponseTransfer = $this->tester->getFacade()->getRecurringScheduleReview($criteriaTransfer);

        // Assert
        $this->assertNull($recurringScheduleReviewResponseTransfer->getRecurringSchedule());
    }

    public function testCollectsNonItemBlockingErrors(): void
    {
        // Arrange
        $uuid = $this->haveScheduleWithItems([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
        ]);

        $this->tester->setScheduleValidatorPlugins([
            new FixedBlockingErrorScheduleValidatorPlugin(static::BLOCKING_ERROR_MESSAGE),
        ]);

        // Act
        $recurringScheduleReviewResponseTransfer = $this->tester->getFacade()->getRecurringScheduleReview($this->createReviewCriteria($uuid));

        // Assert
        $this->assertCount(1, $recurringScheduleReviewResponseTransfer->getBlockingErrors());
        $this->assertSame(
            static::BLOCKING_ERROR_MESSAGE,
            $recurringScheduleReviewResponseTransfer->getBlockingErrors()->offsetGet(0)->getMessageOrFail(),
        );
    }

    public function testDoesNotPersistScheduleWhenBuildingReview(): void
    {
        // Arrange — a price-increase flag during review must NOT rewrite the stored reference price.
        $uuid = $this->haveScheduleWithItems([
            [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 2, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
        ]);

        $this->tester->setScheduleValidatorPlugins([
            $this->tester->createFixedScheduleValidatorPlugin(static::SKU_A, true, SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED, 800),
        ]);

        // Act
        $this->tester->getFacade()->getRecurringScheduleReview($this->createReviewCriteria($uuid));

        // Assert — the stored reference price is unchanged (re-validation is read-only).
        $recurringScheduleItemEntity = SpyRecurringScheduleItemQuery::create()
            ->filterBySku(static::SKU_A)
            ->findOne();
        $this->assertNotNull($recurringScheduleItemEntity);
        $this->assertSame(500, $recurringScheduleItemEntity->getReferenceGrossPrice());
    }

    public function testSkipsReviewValidationWhenScheduleIsNotAwaitingReview(): void
    {
        // Arrange — an active schedule with a blocking validator wired: the guard must skip the validation pipeline.
        $uuid = $this->haveScheduleWithItems(
            [
                [RecurringScheduleItemTransfer::SKU => static::SKU_A, RecurringScheduleItemTransfer::QUANTITY => 1, RecurringScheduleItemTransfer::REFERENCE_GROSS_PRICE => 500],
            ],
            SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
        );

        $this->tester->setScheduleValidatorPlugins([
            new FixedBlockingErrorScheduleValidatorPlugin(static::BLOCKING_ERROR_MESSAGE),
        ]);

        // Act
        $recurringScheduleReviewResponseTransfer = $this->tester->getFacade()->getRecurringScheduleReview($this->createReviewCriteria($uuid));

        // Assert — the schedule is returned, but the blocking validator never ran.
        $this->assertNotNull($recurringScheduleReviewResponseTransfer->getRecurringSchedule());
        $this->assertCount(0, $recurringScheduleReviewResponseTransfer->getBlockingErrors());
        $this->assertCount(0, $recurringScheduleReviewResponseTransfer->getFlaggedItems());
    }

    /**
     * @param array<int, array<string, mixed>> $itemOverridesList
     */
    protected function haveScheduleWithItems(
        array $itemOverridesList,
        string $status = SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
    ): string {
        $customerTransfer = $this->tester->haveCustomer();
        $this->tester->pinMailFacadeDependency();

        $recurringScheduleTransfer = $this->tester->haveRecurringSchedule((int)$customerTransfer->getIdCustomer(), [
            RecurringScheduleTransfer::PRICE_MODE => static::PRICE_MODE_GROSS,
            RecurringScheduleTransfer::STATUS => $status,
            static::OVERRIDE_BUILD_QUOTE_DATA => true,
        ]);

        foreach ($itemOverridesList as $itemOverrides) {
            $this->tester->haveRecurringScheduleItem(
                $recurringScheduleTransfer->getIdRecurringScheduleOrFail(),
                $itemOverrides + [static::OVERRIDE_BUILD_ITEM_DATA => true],
            );
        }

        return $recurringScheduleTransfer->getUuidOrFail();
    }

    protected function createReviewCriteria(string $uuid): RecurringScheduleCriteriaTransfer
    {
        return (new RecurringScheduleCriteriaTransfer())
            ->setRecurringScheduleConditions(
                (new RecurringScheduleConditionsTransfer())
                    ->addUuid($uuid)
                    ->setIsWithItems(true)
                    ->setIsGroupedByGroupKey(true),
            );
    }
}
