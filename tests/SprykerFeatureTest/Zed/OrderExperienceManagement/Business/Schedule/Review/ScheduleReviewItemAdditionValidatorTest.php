<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Spryker\Zed\Calculation\Business\CalculationFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\BundleItemClassifierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\ScheduleReviewItemAdditionValidator;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductUnitValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\AddedItemValidatorPluginInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group ScheduleReviewItemAdditionValidatorTest
 * Add your own group annotations below this line
 */
class ScheduleReviewItemAdditionValidatorTest extends Unit
{
    protected const string SKU = '041_25904691';

    protected const string GLOSSARY_KEY_UNIT = 'recurring_orders.review.add_product.error.measurement_unit_not_supported';

    protected const string GLOSSARY_KEY_PLUGIN = 'self_service_portal.recurring_order.add_product.error.service_delivery_required';

    protected const int ID_SHIPMENT_METHOD = 1;

    protected const string MESSAGE_HARD_MAXIMUM_THRESHOLD = 'sales-order-threshold.hard-maximum-threshold.de.eur.message';

    /**
     * @uses \Spryker\Zed\SalesOrderThreshold\Business\HardThresholdCheck\HardThresholdChecker::THRESHOLD_GLOSSARY_PARAMETER
     */
    protected const string THRESHOLD_GLOSSARY_PARAMETER = '{{threshold}}';

    protected const string THRESHOLD_FORMATTED = '€1,000.00';

    /**
     * The module's own unit rules run before the plugin stack, so foreign rules stay reachable for every
     * addition the module itself accepts.
     */
    public function testConsultsThePluginStackWhenTheOwnUnitRulesAcceptTheAddition(): void
    {
        // Arrange
        $addedItemValidatorPluginMock = $this->createMock(AddedItemValidatorPluginInterface::class);
        $addedItemValidatorPluginMock
            ->expects($this->once())
            ->method('validate')
            ->willReturn((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_PLUGIN));

        $scheduleReviewItemAdditionValidator = $this->createValidator(null, [$addedItemValidatorPluginMock]);

        // Act
        $errorTransfer = $scheduleReviewItemAdditionValidator->validate(
            [$this->createAddition()],
            [0 => [$this->createResolvedItem()]],
            $this->createSchedule(),
        );

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_PLUGIN, $errorTransfer->getMessage());
    }

    public function testReturnsTheOwnUnitErrorAndSkipsThePluginStack(): void
    {
        // Arrange
        $addedItemValidatorPluginMock = $this->createMock(AddedItemValidatorPluginInterface::class);
        $addedItemValidatorPluginMock->expects($this->never())->method('validate');

        $scheduleReviewItemAdditionValidator = $this->createValidator(
            (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_UNIT),
            [$addedItemValidatorPluginMock],
        );

        // Act
        $errorTransfer = $scheduleReviewItemAdditionValidator->validate(
            [$this->createAddition()],
            [0 => [$this->createResolvedItem()]],
            $this->createSchedule(),
        );

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_UNIT, $errorTransfer->getMessage());
    }

    public function testPassesTheResolvedItemsAndTheRequestedSkuToTheUnitValidator(): void
    {
        // Arrange
        $itemTransfer = $this->createResolvedItem();

        $addedItemProductUnitValidatorMock = $this->createMock(AddedItemProductUnitValidatorInterface::class);
        $addedItemProductUnitValidatorMock
            ->expects($this->once())
            ->method('validate')
            ->with([$itemTransfer], static::SKU)
            ->willReturn((new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_UNIT));

        $scheduleReviewItemAdditionValidator = new ScheduleReviewItemAdditionValidator(
            $this->createMock(PlaceableQuoteDeserializerInterface::class),
            $this->createMock(CheckoutFacadeInterface::class),
            $this->createMock(BundleItemClassifierInterface::class),
            $this->createMock(CalculationFacadeInterface::class),
            $addedItemProductUnitValidatorMock,
            [],
        );

        // Act
        $errorTransfer = $scheduleReviewItemAdditionValidator->validate(
            [$this->createAddition()],
            [0 => [$itemTransfer]],
            $this->createSchedule(),
        );

        // Assert
        $this->assertNotNull($errorTransfer);
    }

    /**
     * Nothing may reach the unit rules before the module's own availability check has passed.
     */
    public function testUnitValidatorIsNotAskedWhenTheAdditionResolvedToNoItems(): void
    {
        // Arrange
        $addedItemProductUnitValidatorMock = $this->createMock(AddedItemProductUnitValidatorInterface::class);
        $addedItemProductUnitValidatorMock->expects($this->never())->method('validate');

        $scheduleReviewItemAdditionValidator = new ScheduleReviewItemAdditionValidator(
            $this->createMock(PlaceableQuoteDeserializerInterface::class),
            $this->createMock(CheckoutFacadeInterface::class),
            $this->createMock(BundleItemClassifierInterface::class),
            $this->createMock(CalculationFacadeInterface::class),
            $addedItemProductUnitValidatorMock,
            [],
        );

        // Act
        $errorTransfer = $scheduleReviewItemAdditionValidator->validate(
            [$this->createAddition()],
            [0 => []],
            $this->createSchedule(),
        );

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame('recurring_orders.review.add_product.error.not_available', $errorTransfer->getMessage());
    }

    public function testCarriesCheckoutErrorParametersToTheAdditionError(): void
    {
        // Arrange
        $placeableQuoteDeserializerMock = $this->createMock(PlaceableQuoteDeserializerInterface::class);
        $placeableQuoteDeserializerMock->method('deserialize')->willReturn(new QuoteTransfer());

        $calculationFacadeMock = $this->createMock(CalculationFacadeInterface::class);
        $calculationFacadeMock->method('recalculateQuote')->willReturnArgument(0);

        $checkoutFacadeMock = $this->createMock(CheckoutFacadeInterface::class);
        $checkoutFacadeMock->method('isPlaceableOrder')->willReturn(
            (new CheckoutResponseTransfer())
                ->setIsSuccess(false)
                ->addError(
                    (new CheckoutErrorTransfer())
                        ->setMessage(static::MESSAGE_HARD_MAXIMUM_THRESHOLD)
                        ->setParameters([static::THRESHOLD_GLOSSARY_PARAMETER => static::THRESHOLD_FORMATTED]),
                ),
        );

        $scheduleReviewItemAdditionValidator = new ScheduleReviewItemAdditionValidator(
            $placeableQuoteDeserializerMock,
            $checkoutFacadeMock,
            $this->createMock(BundleItemClassifierInterface::class),
            $calculationFacadeMock,
            $this->createMock(AddedItemProductUnitValidatorInterface::class),
            [],
        );

        // Act
        $errorTransfer = $scheduleReviewItemAdditionValidator->validate(
            [$this->createAddition()],
            [0 => [$this->createResolvedItem()]],
            $this->createSchedule(),
        );

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::MESSAGE_HARD_MAXIMUM_THRESHOLD, $errorTransfer->getMessage());
        $this->assertSame(
            [static::THRESHOLD_GLOSSARY_PARAMETER => static::THRESHOLD_FORMATTED],
            $errorTransfer->getParameters(),
        );
    }

    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\AddedItemValidatorPluginInterface> $addedItemValidatorPlugins
     */
    protected function createValidator(
        ?ErrorTransfer $unitErrorTransfer,
        array $addedItemValidatorPlugins,
    ): ScheduleReviewItemAdditionValidator {
        $addedItemProductUnitValidatorMock = $this->createMock(AddedItemProductUnitValidatorInterface::class);
        $addedItemProductUnitValidatorMock->method('validate')->willReturn($unitErrorTransfer);

        return new ScheduleReviewItemAdditionValidator(
            $this->createMock(PlaceableQuoteDeserializerInterface::class),
            $this->createMock(CheckoutFacadeInterface::class),
            $this->createMock(BundleItemClassifierInterface::class),
            $this->createMock(CalculationFacadeInterface::class),
            $addedItemProductUnitValidatorMock,
            $addedItemValidatorPlugins,
        );
    }

    protected function createAddition(): RecurringScheduleItemAdditionTransfer
    {
        return (new RecurringScheduleItemAdditionTransfer())->setSku(static::SKU)->setQuantity(1);
    }

    protected function createResolvedItem(): ItemTransfer
    {
        return (new ItemTransfer())
            ->setSku(static::SKU)
            ->setUnitGrossPrice(1000)
            ->setShipment(
                (new ShipmentTransfer())->setMethod(
                    (new ShipmentMethodTransfer())->setIdShipmentMethod(static::ID_SHIPMENT_METHOD),
                ),
            );
    }

    protected function createSchedule(): RecurringScheduleTransfer
    {
        return (new RecurringScheduleTransfer())->setQuoteData('{}');
    }
}
