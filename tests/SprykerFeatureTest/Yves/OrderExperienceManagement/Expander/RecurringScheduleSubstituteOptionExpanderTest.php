<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Expander;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ConcreteAlternativeProductCollectionTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductCriteriaTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductAlternativeStorage\ProductAlternativeStorageClientInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Expander\RecurringScheduleSubstituteOptionExpander;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Expander
 * @group RecurringScheduleSubstituteOptionExpanderTest
 */
class RecurringScheduleSubstituteOptionExpanderTest extends Unit
{
    protected const string SKU_DISCONTINUED = '178_29658415';

    protected const string SKU_OTHER = '139_24699831';

    protected const string SKU_ALTERNATIVE = '179_30000001';

    protected const string GROUP_KEY_ORIGINAL_LINE = '178_29658415_a684eceee76fc522773286a895bc8436';

    protected const string GROUP_KEY_ADDED_LINE = 'recurring-order-added-item-0_178_29658415';

    protected const string PRODUCT_NAME_ALTERNATIVE = 'Samsung Galaxy Tab S3';

    protected const string LOCALE_NAME = 'de_DE';

    protected const string PRICE_DELTA_SAME = 'recurring_orders.review.substitute.delta_same';

    protected const string PRICE_DELTA_LOWER = 'recurring_orders.review.substitute.delta_lower';

    protected const string PRICE_DELTA_HIGHER = 'recurring_orders.review.substitute.delta_higher';

    protected const int PRICE_ALTERNATIVE = 5000;

    protected const int PRICE_LINE_ABOVE_ALTERNATIVE = 6908;

    protected const int PRICE_LINE_BELOW_ALTERNATIVE = 3000;

    /**
     * Regression: two flagged lines may carry the same concrete product under different group keys
     * (e.g. a scheduled line plus a line added through a previous review), and both must be substitutable.
     */
    public function testExpandWithSubstituteOptionsAddsOptionsToEveryLineSharingSku(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createReviewResponse([
            $this->createItemReview(static::GROUP_KEY_ORIGINAL_LINE, static::SKU_DISCONTINUED),
            $this->createItemReview(static::GROUP_KEY_ADDED_LINE, static::SKU_DISCONTINUED),
        ]);
        $recurringScheduleSubstituteOptionExpander = $this->createExpander($this->createAlternativeProductCollection());

        // Act
        $recurringScheduleReviewResponseTransfer = $recurringScheduleSubstituteOptionExpander->expandWithSubstituteOptions(
            $recurringScheduleReviewResponseTransfer,
        );

        // Assert
        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            $substituteOptions = $recurringScheduleItemReviewTransfer->getSubstituteOptions()->getArrayCopy();

            $this->assertCount(1, $substituteOptions);
            $this->assertSame(static::SKU_ALTERNATIVE, $substituteOptions[0]->getSku());
            $this->assertSame(static::PRODUCT_NAME_ALTERNATIVE, $substituteOptions[0]->getProductName());
            $this->assertSame(static::PRICE_ALTERNATIVE, $substituteOptions[0]->getPrice());
            $this->assertTrue($substituteOptions[0]->getIsAvailable());
        }
    }

    public function testExpandWithSubstituteOptionsResolvesPriceDeltaLabelPerLine(): void
    {
        // Arrange: both lines share the SKU but were scheduled at a different price.
        $recurringScheduleReviewResponseTransfer = $this->createReviewResponse([
            $this->createItemReview(static::GROUP_KEY_ORIGINAL_LINE, static::SKU_DISCONTINUED, currentPrice: static::PRICE_LINE_ABOVE_ALTERNATIVE),
            $this->createItemReview(static::GROUP_KEY_ADDED_LINE, static::SKU_DISCONTINUED, currentPrice: static::PRICE_LINE_BELOW_ALTERNATIVE),
            $this->createItemReview(static::SKU_OTHER, static::SKU_OTHER, currentPrice: static::PRICE_ALTERNATIVE),
        ]);
        $recurringScheduleSubstituteOptionExpander = $this->createExpander($this->createAlternativeProductCollection([
            static::SKU_DISCONTINUED,
            static::SKU_OTHER,
        ]));

        // Act
        $recurringScheduleReviewResponseTransfer = $recurringScheduleSubstituteOptionExpander->expandWithSubstituteOptions(
            $recurringScheduleReviewResponseTransfer,
        );

        // Assert
        $priceDeltaLabels = $this->extractPriceDeltaLabels($recurringScheduleReviewResponseTransfer);

        $this->assertSame(
            [static::PRICE_DELTA_LOWER, static::PRICE_DELTA_HIGHER, static::PRICE_DELTA_SAME],
            $priceDeltaLabels,
        );
    }

    public function testExpandWithSubstituteOptionsRequestsEachSkuOnce(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createReviewResponse([
            $this->createItemReview(static::GROUP_KEY_ORIGINAL_LINE, static::SKU_DISCONTINUED),
            $this->createItemReview(static::GROUP_KEY_ADDED_LINE, static::SKU_DISCONTINUED),
        ]);
        $concreteAlternativeProductCriteriaTransfer = null;
        $recurringScheduleSubstituteOptionExpander = $this->createExpander(
            $this->createAlternativeProductCollection(),
            criteriaAssertion: function (ConcreteAlternativeProductCriteriaTransfer $criteriaTransfer) use (&$concreteAlternativeProductCriteriaTransfer): void {
                $concreteAlternativeProductCriteriaTransfer = $criteriaTransfer;
            },
        );

        // Act
        $recurringScheduleSubstituteOptionExpander->expandWithSubstituteOptions($recurringScheduleReviewResponseTransfer);

        // Assert
        $concreteAlternativeProductConditionsTransfer = $concreteAlternativeProductCriteriaTransfer?->getConcreteAlternativeProductConditions();

        $this->assertSame([static::SKU_DISCONTINUED], $concreteAlternativeProductConditionsTransfer?->getSkus());
        $this->assertSame(static::LOCALE_NAME, $concreteAlternativeProductConditionsTransfer?->getLocaleName());
    }

    public function testExpandWithSubstituteOptionsSkipsItemsWithoutGroupKeySkuOrSubstitutableReason(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createReviewResponse([
            $this->createItemReview(null, static::SKU_DISCONTINUED),
            $this->createItemReview(static::GROUP_KEY_ADDED_LINE, null),
            $this->createItemReview(static::GROUP_KEY_ORIGINAL_LINE, static::SKU_DISCONTINUED, [
                SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED,
            ]),
        ]);
        $recurringScheduleSubstituteOptionExpander = $this->createExpander(expectStorageCall: false);

        // Act
        $recurringScheduleReviewResponseTransfer = $recurringScheduleSubstituteOptionExpander->expandWithSubstituteOptions(
            $recurringScheduleReviewResponseTransfer,
        );

        // Assert
        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            $this->assertCount(0, $recurringScheduleItemReviewTransfer->getSubstituteOptions());
        }
    }

    public function testExpandWithSubstituteOptionsIgnoresAlternativesOfNotFlaggedSku(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createReviewResponse([
            $this->createItemReview(static::GROUP_KEY_ORIGINAL_LINE, static::SKU_DISCONTINUED),
        ]);
        $recurringScheduleSubstituteOptionExpander = $this->createExpander(
            $this->createAlternativeProductCollection([static::SKU_OTHER]),
        );

        // Act
        $recurringScheduleReviewResponseTransfer = $recurringScheduleSubstituteOptionExpander->expandWithSubstituteOptions(
            $recurringScheduleReviewResponseTransfer,
        );

        // Assert
        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            $this->assertCount(0, $recurringScheduleItemReviewTransfer->getSubstituteOptions());
        }
    }

    public function testExpandWithSubstituteOptionsSkipsStorageCallWhenNoFlaggedItems(): void
    {
        // Arrange
        $recurringScheduleReviewResponseTransfer = $this->createReviewResponse([]);
        $recurringScheduleSubstituteOptionExpander = $this->createExpander(expectStorageCall: false);

        // Act
        $recurringScheduleReviewResponseTransfer = $recurringScheduleSubstituteOptionExpander->expandWithSubstituteOptions(
            $recurringScheduleReviewResponseTransfer,
        );

        // Assert
        $this->assertCount(0, $recurringScheduleReviewResponseTransfer->getFlaggedItems());
    }

    /**
     * @return array<int, string|null> One entry per flagged item, in flagged item order.
     */
    protected function extractPriceDeltaLabels(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): array {
        $priceDeltaLabels = [];

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            foreach ($recurringScheduleItemReviewTransfer->getSubstituteOptions() as $recurringScheduleSubstituteOptionTransfer) {
                $priceDeltaLabels[] = $recurringScheduleSubstituteOptionTransfer->getPriceDeltaLabel();
            }
        }

        return $priceDeltaLabels;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $recurringScheduleItemReviewTransfers
     */
    protected function createReviewResponse(array $recurringScheduleItemReviewTransfers): RecurringScheduleReviewResponseTransfer
    {
        $recurringScheduleReviewResponseTransfer = new RecurringScheduleReviewResponseTransfer();

        foreach ($recurringScheduleItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            $recurringScheduleReviewResponseTransfer->addFlaggedItem($recurringScheduleItemReviewTransfer);
        }

        return $recurringScheduleReviewResponseTransfer;
    }

    /**
     * @param array<int, string>|null $reviewReasons
     */
    protected function createItemReview(
        ?string $groupKey,
        ?string $sku,
        ?array $reviewReasons = null,
        ?int $currentPrice = null,
    ): RecurringScheduleItemReviewTransfer {
        $recurringScheduleItemTransfer = (new RecurringScheduleItemTransfer())
            ->setGroupKey($groupKey)
            ->setSku($sku);

        return (new RecurringScheduleItemReviewTransfer())
            ->setRecurringScheduleItem($recurringScheduleItemTransfer)
            ->setReviewReasons($reviewReasons ?? [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_DISCONTINUED])
            ->setCurrentPrice($currentPrice);
    }

    /**
     * @param array<int, string>|null $skus
     */
    protected function createAlternativeProductCollection(?array $skus = null): ConcreteAlternativeProductCollectionTransfer
    {
        $concreteAlternativeProductCollectionTransfer = new ConcreteAlternativeProductCollectionTransfer();
        $productViewTransfer = (new ProductViewTransfer())
            ->setSku(static::SKU_ALTERNATIVE)
            ->setName(static::PRODUCT_NAME_ALTERNATIVE)
            ->setPrice(static::PRICE_ALTERNATIVE)
            ->setAvailable(true);

        foreach ($skus ?? [static::SKU_DISCONTINUED] as $sku) {
            $concreteAlternativeProductCollectionTransfer->addConcreteAlternativeProduct(
                (new ConcreteAlternativeProductTransfer())
                    ->setSku($sku)
                    ->addAlternativeProduct($productViewTransfer),
            );
        }

        return $concreteAlternativeProductCollectionTransfer;
    }

    protected function createExpander(
        ?ConcreteAlternativeProductCollectionTransfer $concreteAlternativeProductCollectionTransfer = null,
        bool $expectStorageCall = true,
        ?callable $criteriaAssertion = null,
    ): RecurringScheduleSubstituteOptionExpander {
        $productAlternativeStorageClientMock = $this->createMock(ProductAlternativeStorageClientInterface::class);
        $productAlternativeStorageClientMock
            ->expects($expectStorageCall ? $this->once() : $this->never())
            ->method('getConcreteAlternativeProductCollection')
            ->willReturnCallback(
                function (ConcreteAlternativeProductCriteriaTransfer $concreteAlternativeProductCriteriaTransfer) use (
                    $concreteAlternativeProductCollectionTransfer,
                    $criteriaAssertion,
                ): ConcreteAlternativeProductCollectionTransfer {
                    if ($criteriaAssertion !== null) {
                        $criteriaAssertion($concreteAlternativeProductCriteriaTransfer);
                    }

                    return $concreteAlternativeProductCollectionTransfer ?? new ConcreteAlternativeProductCollectionTransfer();
                },
            );

        $localeClientMock = $this->createMock(LocaleClientInterface::class);
        $localeClientMock->method('getCurrentLocale')->willReturn(static::LOCALE_NAME);

        $configMock = $this->createMock(OrderExperienceManagementConfig::class);
        $configMock->method('getSubstitutableReviewReasons')->willReturn(
            SharedOrderExperienceManagementConfig::SUBSTITUTABLE_REVIEW_REASON_GROUPS,
        );

        return new RecurringScheduleSubstituteOptionExpander($productAlternativeStorageClientMock, $localeClientMock, $configMock);
    }
}
