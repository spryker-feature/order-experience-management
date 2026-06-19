<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use Spryker\Zed\PriceCartConnector\Business\PriceCartConnectorFacadeInterface;
use Spryker\Zed\ProductPackagingUnit\Business\ProductPackagingUnitFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator\PriceScheduleValidatorPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group ScheduleValidator
 * @group PriceScheduleValidatorPluginTest
 * Add your own group annotations below this line
 */
class PriceScheduleValidatorPluginTest extends Unit
{
    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_GROSS
     */
    protected const string PRICE_MODE_GROSS = 'GROSS_MODE';

    /**
     * @uses \Spryker\Shared\Price\PriceConfig::PRICE_MODE_NET
     */
    protected const string PRICE_MODE_NET = 'NET_MODE';

    protected const string SKU_COMMON = 'common-product';

    protected const string SKU_MERCHANT = 'merchant-product';

    protected const string SKU_CONFIGURED_BUNDLE_FIRST = 'configured-bundle-first';

    protected const string SKU_CONFIGURED_BUNDLE_SECOND = 'configured-bundle-second';

    protected const string SKU_PACKAGING = 'packaging-product';

    protected const string SKU_MEASUREMENT = 'measurement-product';

    protected const string SKU_BUNDLE = 'product-bundle';

    protected const string GROUP_KEY_COMMON = 'group-common';

    protected const string GROUP_KEY_MERCHANT = 'group-merchant';

    protected const string GROUP_KEY_CONFIGURED_BUNDLE_FIRST = 'group-configured-first';

    protected const string GROUP_KEY_CONFIGURED_BUNDLE_SECOND = 'group-configured-second';

    protected const string GROUP_KEY_PACKAGING = 'group-packaging';

    protected const string GROUP_KEY_MEASUREMENT = 'group-measurement';

    protected const string GROUP_KEY_BUNDLE_CHILD = 'group-bundle-child';

    protected const string BUNDLE_ITEM_IDENTIFIER = 'bundle-identifier-1';

    protected const string CONFIGURED_BUNDLE_GROUP_KEY = 'configured-bundle-group';

    protected const string MERCHANT_REFERENCE = 'mer000001';

    public function testFlagsCommonProductWhenCurrentPriceIncreased(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createGroupedScheduleItem(static::SKU_COMMON, static::GROUP_KEY_COMMON, 1000, 1000),
        ]);
        $plugin = $this->createPlugin([static::GROUP_KEY_COMMON => [1500, 1500]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(static::SKU_COMMON, $itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku());
        $this->assertSame(1000, $itemReviewTransfer->getPreviousPrice());
        $this->assertSame(1500, $itemReviewTransfer->getCurrentPrice());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testDoesNotFlagCommonProductWhenPriceIsStable(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createGroupedScheduleItem(static::SKU_COMMON, static::GROUP_KEY_COMMON, 1000, 1000),
        ]);
        $plugin = $this->createPlugin([static::GROUP_KEY_COMMON => [1000, 1000]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertTrue($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
    }

    public function testFlagsCommonProductWhenCurrentPriceUnavailable(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createGroupedScheduleItem(static::SKU_COMMON, static::GROUP_KEY_COMMON, 1000, 1000),
        ]);

        $plugin = $this->createPlugin([]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE],
            $resultTransfer->getItemReviews()->offsetGet(0)->getReviewReasons(),
        );
    }

    public function testFlagsMerchantProductWhenCurrentPriceIncreased(): void
    {
        // Arrange
        $scheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_MERCHANT, static::GROUP_KEY_MERCHANT, 2000, 2000, [
            ItemTransfer::MERCHANT_REFERENCE => static::MERCHANT_REFERENCE,
        ])->setMerchantReference(static::MERCHANT_REFERENCE);
        $recurringScheduleTransfer = $this->createScheduleTransfer([$scheduleItemTransfer]);

        $plugin = $this->createPlugin([static::GROUP_KEY_MERCHANT => [2500, 2500]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(static::SKU_MERCHANT, $itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku());
        $this->assertSame(2500, $itemReviewTransfer->getCurrentPrice());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testFlagsConfigurableBundleItemsWhenCurrentPriceIncreased(): void
    {
        // Arrange
        $firstScheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_CONFIGURED_BUNDLE_FIRST, static::GROUP_KEY_CONFIGURED_BUNDLE_FIRST, 700, 700, [
            RecurringScheduleItemTransfer::CONFIGURED_BUNDLE_GROUP_KEY => static::CONFIGURED_BUNDLE_GROUP_KEY,
        ]);
        $secondScheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_CONFIGURED_BUNDLE_SECOND, static::GROUP_KEY_CONFIGURED_BUNDLE_SECOND, 300, 300, [
            RecurringScheduleItemTransfer::CONFIGURED_BUNDLE_GROUP_KEY => static::CONFIGURED_BUNDLE_GROUP_KEY,
        ]);
        $recurringScheduleTransfer = $this->createScheduleTransfer([$firstScheduleItemTransfer, $secondScheduleItemTransfer]);

        $plugin = $this->createPlugin([
            static::GROUP_KEY_CONFIGURED_BUNDLE_FIRST => [900, 900],
            static::GROUP_KEY_CONFIGURED_BUNDLE_SECOND => [450, 450],
        ]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(2, $resultTransfer->getItemReviews());

        $reviewReasonsBySku = [];
        foreach ($resultTransfer->getItemReviews() as $itemReviewTransfer) {
            $reviewReasonsBySku[$itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku()] = $itemReviewTransfer->getReviewReasons();
        }

        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $reviewReasonsBySku[static::SKU_CONFIGURED_BUNDLE_FIRST] ?? null,
        );
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $reviewReasonsBySku[static::SKU_CONFIGURED_BUNDLE_SECOND] ?? null,
        );
    }

    public function testFlagsPackagingUnitItemWhenCurrentPriceIncreased(): void
    {
        // Arrange
        $scheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_PACKAGING, static::GROUP_KEY_PACKAGING, 500, 500, [
            ItemTransfer::AMOUNT => '5',
        ]);
        $recurringScheduleTransfer = $this->createScheduleTransfer([$scheduleItemTransfer]);

        $plugin = $this->createPlugin([static::GROUP_KEY_PACKAGING => [800, 800]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(static::SKU_PACKAGING, $itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku());
        $this->assertSame(800, $itemReviewTransfer->getCurrentPrice());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testFlagsMeasurementUnitItemWhenCurrentPriceIncreased(): void
    {
        // Arrange
        $scheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_MEASUREMENT, static::GROUP_KEY_MEASUREMENT, 400, 400, [
            ItemTransfer::AMOUNT => '3',
            ItemTransfer::AMOUNT_SALES_UNIT => ['idProductMeasurementSalesUnit' => 1],
        ]);
        $recurringScheduleTransfer = $this->createScheduleTransfer([$scheduleItemTransfer]);

        $plugin = $this->createPlugin([static::GROUP_KEY_MEASUREMENT => [650, 650]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(static::SKU_MEASUREMENT, $itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku());
        $this->assertSame(650, $itemReviewTransfer->getCurrentPrice());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testFlagsBundleProductWhenCurrentPriceIncreased(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createBundleScheduleItem(static::SKU_BUNDLE, static::BUNDLE_ITEM_IDENTIFIER, 5000, 4500),
        ]);

        $plugin = $this->createPlugin([static::BUNDLE_ITEM_IDENTIFIER => [6000, 6000]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(static::SKU_BUNDLE, $itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku());
        $this->assertSame(5000, $itemReviewTransfer->getPreviousPrice());
        $this->assertSame(6000, $itemReviewTransfer->getCurrentPrice());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testDoesNotFlagBundleProductWhenCurrentPriceUnavailable(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createBundleScheduleItem(static::SKU_BUNDLE, static::BUNDLE_ITEM_IDENTIFIER, 5000, 4500),
        ]);

        $plugin = $this->createPlugin([]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertTrue($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
    }

    public function testSkipsBundleChildItemsWhenCheckingItemPriceDrift(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createGroupedScheduleItem(static::SKU_BUNDLE, static::GROUP_KEY_BUNDLE_CHILD, 1000, 900, [
                ItemTransfer::RELATED_BUNDLE_ITEM_IDENTIFIER => static::BUNDLE_ITEM_IDENTIFIER,
            ]),
        ]);

        $plugin = $this->createPlugin([static::GROUP_KEY_BUNDLE_CHILD => [2000, 2000]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertTrue($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
    }

    public function testFlagsCommonProductInNetPriceModeWhenCurrentPriceIncreased(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer(
            [$this->createGroupedScheduleItem(static::SKU_COMMON, static::GROUP_KEY_COMMON, 1200, 1000)],
            static::PRICE_MODE_NET,
        );

        $plugin = $this->createPlugin([static::GROUP_KEY_COMMON => [1200, 1400]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(1000, $itemReviewTransfer->getPreviousPrice());
        $this->assertSame(1400, $itemReviewTransfer->getCurrentPrice());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testReturnsResultUnchangedWhenQuoteDataIsNull(): void
    {
        // Arrange
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->addItem(
            $this->createGroupedScheduleItem(static::SKU_COMMON, static::GROUP_KEY_COMMON, 1000, 1000),
        );

        $priceCartConnectorFacadeMock = $this->createMock(PriceCartConnectorFacadeInterface::class);
        $priceCartConnectorFacadeMock->expects($this->never())->method('addPriceToItems');

        $plugin = $this->createPluginFromMocks($priceCartConnectorFacadeMock, $this->createProductPackagingUnitFacadeMock());

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertTrue($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
    }

    public function testFlagsPackagingUnitItemWhenAmountPriceIncreased(): void
    {
        // Arrange
        $scheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_PACKAGING, static::GROUP_KEY_PACKAGING, 500, 500, [
            ItemTransfer::AMOUNT => '5',
        ]);
        $recurringScheduleTransfer = $this->createScheduleTransfer([$scheduleItemTransfer]);

        $plugin = $this->createPluginWithAmountPricing(
            [static::GROUP_KEY_PACKAGING => [500, 500]],
            [static::GROUP_KEY_PACKAGING => [800, 800]],
        );

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(static::SKU_PACKAGING, $itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku());
        $this->assertSame(500, $itemReviewTransfer->getPreviousPrice());
        $this->assertSame(800, $itemReviewTransfer->getCurrentPrice());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testFlagsPackagingUnitItemWhenAmountPriceUnavailable(): void
    {
        // Arrange
        $scheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_PACKAGING, static::GROUP_KEY_PACKAGING, 500, 500, [
            ItemTransfer::AMOUNT => '5',
        ]);
        $recurringScheduleTransfer = $this->createScheduleTransfer([$scheduleItemTransfer]);

        $plugin = $this->createPluginWithAmountPricing(
            [static::GROUP_KEY_PACKAGING => [500, 500]],
            [static::GROUP_KEY_PACKAGING => [0, 0]],
        );

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE],
            $resultTransfer->getItemReviews()->offsetGet(0)->getReviewReasons(),
        );
    }

    public function testDoesNotFlagCommonProductWhenCurrentPriceDecreased(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createGroupedScheduleItem(static::SKU_COMMON, static::GROUP_KEY_COMMON, 1000, 1000),
        ]);
        $plugin = $this->createPlugin([static::GROUP_KEY_COMMON => [800, 800]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertTrue($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
    }

    public function testFlagsCommonProductWhenCurrentPriceIsZero(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createGroupedScheduleItem(static::SKU_COMMON, static::GROUP_KEY_COMMON, 1000, 1000),
        ]);
        $plugin = $this->createPlugin([static::GROUP_KEY_COMMON => [0, 0]]);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(0, $itemReviewTransfer->getCurrentPrice());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testValidatesQuoteWithMixedProductTypesAndQuantities(): void
    {
        // Arrange
        $commonScheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_COMMON, static::GROUP_KEY_COMMON, 1000, 1000, [], 2);
        $merchantScheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_MERCHANT, static::GROUP_KEY_MERCHANT, 2000, 2000, [
            ItemTransfer::MERCHANT_REFERENCE => static::MERCHANT_REFERENCE,
        ], 3)->setMerchantReference(static::MERCHANT_REFERENCE);
        $packagingScheduleItemTransfer = $this->createGroupedScheduleItem(static::SKU_PACKAGING, static::GROUP_KEY_PACKAGING, 500, 500, [
            ItemTransfer::AMOUNT => '5',
        ], 4);
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $commonScheduleItemTransfer,
            $merchantScheduleItemTransfer,
            $packagingScheduleItemTransfer,
        ]);

        $plugin = $this->createPluginWithAmountPricing(
            [
                static::GROUP_KEY_COMMON => [1000, 1000],
                static::GROUP_KEY_MERCHANT => [2500, 2500],
                static::GROUP_KEY_PACKAGING => [500, 500],
            ],
            [static::GROUP_KEY_PACKAGING => [700, 700]],
        );

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(2, $resultTransfer->getItemReviews());

        $reviewReasonsBySku = [];
        foreach ($resultTransfer->getItemReviews() as $itemReviewTransfer) {
            $reviewReasonsBySku[$itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku()] = $itemReviewTransfer->getReviewReasons();
        }

        $this->assertArrayNotHasKey(static::SKU_COMMON, $reviewReasonsBySku);
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $reviewReasonsBySku[static::SKU_MERCHANT] ?? null,
        );
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED],
            $reviewReasonsBySku[static::SKU_PACKAGING] ?? null,
        );
    }

    /**
     * @param array<string, mixed> $extraItemData
     */
    protected function createGroupedScheduleItem(
        string $sku,
        string $groupKey,
        int $referenceGrossPrice,
        int $referenceNetPrice,
        array $extraItemData = [],
        int $quantity = 1,
    ): RecurringScheduleItemTransfer {
        $itemData = array_merge([
            ItemTransfer::SKU => $sku,
            ItemTransfer::GROUP_KEY => $groupKey,
        ], $extraItemData);

        return (new RecurringScheduleItemTransfer())
            ->setSku($sku)
            ->setQuantity($quantity)
            ->setGroupKey($groupKey)
            ->setReferenceGrossPrice($referenceGrossPrice)
            ->setReferenceNetPrice($referenceNetPrice)
            ->setItemData($this->encodeItemData($itemData));
    }

    protected function createBundleScheduleItem(
        string $sku,
        string $bundleItemIdentifier,
        int $referenceGrossPrice,
        int $referenceNetPrice,
    ): RecurringScheduleItemTransfer {
        return (new RecurringScheduleItemTransfer())
            ->setSku($sku)
            ->setQuantity(1)
            ->setBundleItemIdentifier($bundleItemIdentifier)
            ->setReferenceGrossPrice($referenceGrossPrice)
            ->setReferenceNetPrice($referenceNetPrice)
            ->setItemData($this->encodeItemData([
                ItemTransfer::SKU => $sku,
                ItemTransfer::BUNDLE_ITEM_IDENTIFIER => $bundleItemIdentifier,
            ]));
    }

    /**
     * @param array<string, mixed> $itemData
     */
    protected function encodeItemData(array $itemData): string
    {
        return json_encode($itemData, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    protected function createScheduleTransfer(array $recurringScheduleItemTransfers, string $priceMode = self::PRICE_MODE_GROSS): RecurringScheduleTransfer
    {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())
            ->setQuoteData($this->encodeItemData([QuoteTransfer::PRICE_MODE => $priceMode]));

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $recurringScheduleTransfer->addItem($recurringScheduleItemTransfer);
        }

        return $recurringScheduleTransfer;
    }

    protected function createValidResult(): RecurringScheduleValidationResultTransfer
    {
        return (new RecurringScheduleValidationResultTransfer())->setIsValid(true);
    }

    /**
     * @param array<string, array<int, int>> $priceMapByKey
     */
    protected function createPlugin(array $priceMapByKey): PriceScheduleValidatorPlugin
    {
        return $this->createPluginFromMocks(
            $this->createPriceCartConnectorFacadeMock($priceMapByKey),
            $this->createProductPackagingUnitFacadeMock(),
        );
    }

    /**
     * @param array<string, array<int, int>> $priceMapByKey Catalog unit prices applied by PriceCartConnector.
     * @param array<string, array<int, int>> $amountPriceMapByKey Amount-aware unit prices applied by ProductPackagingUnit.
     */
    protected function createPluginWithAmountPricing(array $priceMapByKey, array $amountPriceMapByKey): PriceScheduleValidatorPlugin
    {
        return $this->createPluginFromMocks(
            $this->createPriceCartConnectorFacadeMock($priceMapByKey),
            $this->createProductPackagingUnitFacadeMock($amountPriceMapByKey),
        );
    }

    /**
     * @param array<string, array<int, int>> $priceMapByKey
     */
    protected function createPriceCartConnectorFacadeMock(array $priceMapByKey): PriceCartConnectorFacadeInterface
    {
        $priceCartConnectorFacadeMock = $this->createMock(PriceCartConnectorFacadeInterface::class);
        $priceCartConnectorFacadeMock->method('addPriceToItems')->willReturnCallback(
            $this->createPriceApplyingCallback($priceMapByKey),
        );

        return $priceCartConnectorFacadeMock;
    }

    /**
     * @param array<string, array<int, int>> $priceMapByKey
     */
    protected function createPriceApplyingCallback(array $priceMapByKey): callable
    {
        return function (CartChangeTransfer $cartChangeTransfer) use ($priceMapByKey): CartChangeTransfer {
            foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
                $key = $itemTransfer->getGroupKey() ?? $itemTransfer->getBundleItemIdentifier() ?? $itemTransfer->getSku();

                if (!isset($priceMapByKey[$key])) {
                    continue;
                }

                [$unitGrossPrice, $unitNetPrice] = $priceMapByKey[$key];
                $itemTransfer->setUnitGrossPrice($unitGrossPrice)->setUnitNetPrice($unitNetPrice);
            }

            return $cartChangeTransfer;
        };
    }

    /**
     * @param array<string, array<int, int>> $amountPriceMapByKey
     */
    protected function createProductPackagingUnitFacadeMock(array $amountPriceMapByKey = []): ProductPackagingUnitFacadeInterface
    {
        $productPackagingUnitFacadeMock = $this->createMock(ProductPackagingUnitFacadeInterface::class);

        if ($amountPriceMapByKey === []) {
            $productPackagingUnitFacadeMock->method('setCustomAmountPrice')->willReturnArgument(0);

            return $productPackagingUnitFacadeMock;
        }

        $productPackagingUnitFacadeMock->method('setCustomAmountPrice')->willReturnCallback(
            $this->createPriceApplyingCallback($amountPriceMapByKey),
        );

        return $productPackagingUnitFacadeMock;
    }

    protected function createPluginFromMocks(
        PriceCartConnectorFacadeInterface $priceCartConnectorFacadeMock,
        ProductPackagingUnitFacadeInterface $productPackagingUnitFacadeMock,
    ): PriceScheduleValidatorPlugin {
        $businessFactory = new class ($priceCartConnectorFacadeMock, $productPackagingUnitFacadeMock) extends OrderExperienceManagementBusinessFactory {
            public function __construct(
                protected PriceCartConnectorFacadeInterface $priceCartConnectorFacadeMock,
                protected ProductPackagingUnitFacadeInterface $productPackagingUnitFacadeMock,
            ) {
            }

            public function getPriceCartConnectorFacade(): PriceCartConnectorFacadeInterface
            {
                return $this->priceCartConnectorFacadeMock;
            }

            public function getProductPackagingUnitFacade(): ProductPackagingUnitFacadeInterface
            {
                return $this->productPackagingUnitFacadeMock;
            }
        };

        $plugin = new PriceScheduleValidatorPlugin();
        $plugin->setBusinessFactory($businessFactory);

        return $plugin;
    }
}
