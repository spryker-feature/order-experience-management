<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory;
use SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator\CheckoutPlaceabilityScheduleValidatorPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Communication
 * @group Plugin
 * @group ScheduleValidator
 * @group CheckoutPlaceabilityScheduleValidatorPluginTest
 * Add your own group annotations below this line
 */
class CheckoutPlaceabilityScheduleValidatorPluginTest extends Unit
{
    /**
     * @uses \Spryker\Zed\Availability\AvailabilityConfig::ERROR_TYPE_AVAILABILITY
     */
    protected const string ERROR_TYPE_AVAILABILITY = 'Availability';

    /**
     * @uses \Spryker\Zed\ProductBundle\ProductBundleConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string ERROR_TYPE_PRODUCT_BUNDLE = 'ProductBundleUnavailable';

    /**
     * @uses \Spryker\Zed\ProductDiscontinued\ProductDiscontinuedConfig::CHECKOUT_ERROR_TYPE
     */
    protected const string ERROR_TYPE_PRODUCT_DISCONTINUED = 'ProductDiscontinued';

    protected const string ERROR_TYPE_UNKNOWN = 'SomeUnmappedErrorType';

    protected const string SKU_FIRST = 'sku-first';

    protected const string SKU_SECOND = 'sku-second';

    protected const string GROUP_KEY_FIRST = 'group-key-first';

    protected const string GROUP_KEY_SECOND = 'group-key-second';

    protected const string GROUP_KEY_UNKNOWN = 'group-key-not-in-schedule';

    protected const string MESSAGE_GENERIC_FAILURE = 'checkout.error.generic';

    public function testReturnsResultUnchangedWhenNoCheckoutErrors(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        ]);
        $plugin = $this->createPlugin(new CheckoutResponseTransfer());

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertTrue($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
        $this->assertCount(0, $resultTransfer->getBlockingErrors());
    }

    public function testFlagsItemWithUnavailableReviewReasonForAvailabilityError(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        ]);
        $checkoutResponseTransfer = $this->createCheckoutResponse([
            $this->createCheckoutError(static::GROUP_KEY_FIRST, static::ERROR_TYPE_AVAILABILITY),
        ]);
        $plugin = $this->createPlugin($checkoutResponseTransfer);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getBlockingErrors());
        $this->assertCount(1, $resultTransfer->getItemReviews());

        $itemReviewTransfer = $resultTransfer->getItemReviews()->offsetGet(0);
        $this->assertSame(static::SKU_FIRST, $itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE],
            $itemReviewTransfer->getReviewReasons(),
        );
    }

    public function testFlagsItemWithUnavailableReviewReasonForProductBundleError(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        ]);
        $checkoutResponseTransfer = $this->createCheckoutResponse([
            $this->createCheckoutError(static::GROUP_KEY_FIRST, static::ERROR_TYPE_PRODUCT_BUNDLE),
        ]);
        $plugin = $this->createPlugin($checkoutResponseTransfer);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE],
            $resultTransfer->getItemReviews()->offsetGet(0)->getReviewReasons(),
        );
    }

    public function testFlagsItemWithDiscontinuedReviewReasonForProductDiscontinuedError(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        ]);
        $checkoutResponseTransfer = $this->createCheckoutResponse([
            $this->createCheckoutError(static::GROUP_KEY_FIRST, static::ERROR_TYPE_PRODUCT_DISCONTINUED),
        ]);
        $plugin = $this->createPlugin($checkoutResponseTransfer);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_DISCONTINUED],
            $resultTransfer->getItemReviews()->offsetGet(0)->getReviewReasons(),
        );
    }

    public function testFlagsItemWithDefaultReviewReasonForUnmappedErrorType(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        ]);
        $checkoutResponseTransfer = $this->createCheckoutResponse([
            $this->createCheckoutError(static::GROUP_KEY_FIRST, static::ERROR_TYPE_UNKNOWN),
        ]);
        $plugin = $this->createPlugin($checkoutResponseTransfer);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(1, $resultTransfer->getItemReviews());
        $this->assertSame(
            [(new OrderExperienceManagementConfig())->getDefaultReviewReasonGroup()],
            $resultTransfer->getItemReviews()->offsetGet(0)->getReviewReasons(),
        );
    }

    public function testAddsBlockingErrorWhenErrorHasNoGroupKey(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        ]);
        $checkoutResponseTransfer = $this->createCheckoutResponse([
            $this->createCheckoutError(null, static::ERROR_TYPE_AVAILABILITY),
        ]);
        $plugin = $this->createPlugin($checkoutResponseTransfer);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
        $this->assertCount(1, $resultTransfer->getBlockingErrors());
        $this->assertSame(
            static::MESSAGE_GENERIC_FAILURE,
            $resultTransfer->getBlockingErrors()->offsetGet(0)->getMessage(),
        );
    }

    public function testAddsBlockingErrorWhenGroupKeyDoesNotMatchAnyScheduleItem(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        ]);
        $checkoutResponseTransfer = $this->createCheckoutResponse([
            $this->createCheckoutError(static::GROUP_KEY_UNKNOWN, static::ERROR_TYPE_AVAILABILITY),
        ]);
        $plugin = $this->createPlugin($checkoutResponseTransfer);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
        $this->assertCount(1, $resultTransfer->getBlockingErrors());
    }

    public function testAddsBlockingErrorWhenErrorTypeIsMissing(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        ]);
        $checkoutResponseTransfer = $this->createCheckoutResponse([
            $this->createCheckoutError(static::GROUP_KEY_FIRST, null),
        ]);
        $plugin = $this->createPlugin($checkoutResponseTransfer);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
        $this->assertCount(1, $resultTransfer->getBlockingErrors());
    }

    public function testHandlesMixedErrorsAsItemReviewsAndBlockingErrors(): void
    {
        // Arrange
        $recurringScheduleTransfer = $this->createScheduleTransfer([
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
            $this->createScheduleItem(static::SKU_SECOND, static::GROUP_KEY_SECOND),
        ]);
        $checkoutResponseTransfer = $this->createCheckoutResponse([
            $this->createCheckoutError(static::GROUP_KEY_FIRST, static::ERROR_TYPE_AVAILABILITY),
            $this->createCheckoutError(static::GROUP_KEY_SECOND, static::ERROR_TYPE_PRODUCT_DISCONTINUED),
            $this->createCheckoutError(null, static::ERROR_TYPE_AVAILABILITY),
        ]);
        $plugin = $this->createPlugin($checkoutResponseTransfer);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertFalse($resultTransfer->getIsValid());
        $this->assertCount(2, $resultTransfer->getItemReviews());
        $this->assertCount(1, $resultTransfer->getBlockingErrors());

        $reviewReasonsBySku = [];
        foreach ($resultTransfer->getItemReviews() as $itemReviewTransfer) {
            $reviewReasonsBySku[$itemReviewTransfer->getRecurringScheduleItemOrFail()->getSku()] = $itemReviewTransfer->getReviewReasons();
        }

        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_UNAVAILABLE],
            $reviewReasonsBySku[static::SKU_FIRST],
        );
        $this->assertSame(
            [SharedOrderExperienceManagementConfig::REVIEW_REASON_GROUP_DISCONTINUED],
            $reviewReasonsBySku[static::SKU_SECOND],
        );
    }

    public function testReturnsResultUnchangedWhenQuoteDataIsNull(): void
    {
        // Arrange
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->addItem(
            $this->createScheduleItem(static::SKU_FIRST, static::GROUP_KEY_FIRST),
        );

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->expects($this->never())->method('isPlaceableOrder');

        $plugin = $this->createPluginFromMocks($checkoutFacadeMock);

        // Act
        $resultTransfer = $plugin->validate($recurringScheduleTransfer, $this->createValidResult());

        // Assert
        $this->assertTrue($resultTransfer->getIsValid());
        $this->assertCount(0, $resultTransfer->getItemReviews());
        $this->assertCount(0, $resultTransfer->getBlockingErrors());
    }

    protected function createScheduleItem(string $sku, string $groupKey): RecurringScheduleItemTransfer
    {
        return (new RecurringScheduleItemTransfer())
            ->setSku($sku)
            ->setQuantity(1)
            ->setGroupKey($groupKey)
            ->setItemData(json_encode([
                ItemTransfer::SKU => $sku,
                ItemTransfer::GROUP_KEY => $groupKey,
            ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemTransfer> $recurringScheduleItemTransfers
     */
    protected function createScheduleTransfer(array $recurringScheduleItemTransfers): RecurringScheduleTransfer
    {
        $recurringScheduleTransfer = (new RecurringScheduleTransfer())->setQuoteData('{}');

        foreach ($recurringScheduleItemTransfers as $recurringScheduleItemTransfer) {
            $recurringScheduleTransfer->addItem($recurringScheduleItemTransfer);
        }

        return $recurringScheduleTransfer;
    }

    protected function createCheckoutError(?string $groupKey, ?string $errorType): CheckoutErrorTransfer
    {
        return (new CheckoutErrorTransfer())
            ->setGroupKey($groupKey)
            ->setErrorType($errorType)
            ->setMessage(static::MESSAGE_GENERIC_FAILURE);
    }

    /**
     * @param array<\Generated\Shared\Transfer\CheckoutErrorTransfer> $checkoutErrorTransfers
     */
    protected function createCheckoutResponse(array $checkoutErrorTransfers): CheckoutResponseTransfer
    {
        $checkoutResponseTransfer = new CheckoutResponseTransfer();

        foreach ($checkoutErrorTransfers as $checkoutErrorTransfer) {
            $checkoutResponseTransfer->addError($checkoutErrorTransfer);
        }

        return $checkoutResponseTransfer;
    }

    protected function createValidResult(): RecurringScheduleValidationResultTransfer
    {
        return (new RecurringScheduleValidationResultTransfer())->setIsValid(true);
    }

    protected function createPlugin(CheckoutResponseTransfer $checkoutResponseTransfer): CheckoutPlaceabilityScheduleValidatorPlugin
    {
        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->method('isPlaceableOrder')->willReturn($checkoutResponseTransfer);

        return $this->createPluginFromMocks($checkoutFacadeMock);
    }

    protected function createPluginFromMocks(
        CheckoutFacadeInterface $checkoutFacadeMock,
    ): CheckoutPlaceabilityScheduleValidatorPlugin {
        $businessFactory = new class ($checkoutFacadeMock) extends OrderExperienceManagementBusinessFactory {
            public function __construct(
                protected CheckoutFacadeInterface $checkoutFacadeMock,
            ) {
            }

            public function getCheckoutFacade(): CheckoutFacadeInterface
            {
                return $this->checkoutFacadeMock;
            }
        };
        $businessFactory->setConfig(new OrderExperienceManagementConfig());

        $plugin = new CheckoutPlaceabilityScheduleValidatorPlugin();
        $plugin->setBusinessFactory($businessFactory);

        return $plugin;
    }
}
