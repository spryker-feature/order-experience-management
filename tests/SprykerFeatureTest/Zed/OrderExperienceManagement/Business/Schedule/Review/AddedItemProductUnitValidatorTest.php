<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductMeasurementUnitValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductPackagingUnitValidatorInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductUnitValidator;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group AddedItemProductUnitValidatorTest
 * Add your own group annotations below this line
 */
class AddedItemProductUnitValidatorTest extends Unit
{
    protected const string SKU = '215_124';

    protected const string GLOSSARY_KEY_MEASUREMENT_UNIT = 'recurring_orders.review.add_product.error.measurement_unit_not_supported';

    protected const string GLOSSARY_KEY_PACKAGING_UNIT = 'recurring_orders.review.add_product.error.packaging_unit_not_supported';

    public function testAcceptsAdditionWhenBothFlagsAreOffAndNeitherValidatorIsAsked(): void
    {
        // Arrange
        $addedItemProductMeasurementUnitValidatorMock = $this->createMock(AddedItemProductMeasurementUnitValidatorInterface::class);
        $addedItemProductMeasurementUnitValidatorMock->expects($this->never())->method('validate');

        $addedItemProductPackagingUnitValidatorMock = $this->createMock(AddedItemProductPackagingUnitValidatorInterface::class);
        $addedItemProductPackagingUnitValidatorMock->expects($this->never())->method('validate');

        $addedItemProductUnitValidator = new AddedItemProductUnitValidator(
            $addedItemProductMeasurementUnitValidatorMock,
            $addedItemProductPackagingUnitValidatorMock,
            $this->createConfigMock(false, false),
        );

        // Act
        $errorTransfer = $addedItemProductUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    public function testReturnsTheMeasurementUnitErrorVerbatimWhenItsFlagIsOn(): void
    {
        // Arrange
        $addedItemProductUnitValidator = new AddedItemProductUnitValidator(
            $this->createMeasurementUnitValidatorMock($this->createError(static::GLOSSARY_KEY_MEASUREMENT_UNIT)),
            $this->createPackagingUnitValidatorMock(null),
            $this->createConfigMock(true, true),
        );

        // Act
        $errorTransfer = $addedItemProductUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_MEASUREMENT_UNIT, $errorTransfer->getMessage());
        $this->assertSame(['%sku%' => static::SKU], $errorTransfer->getParameters());
    }

    public function testReturnsThePackagingUnitErrorWhenOnlyThePackagingRuleMatches(): void
    {
        // Arrange
        $addedItemProductUnitValidator = new AddedItemProductUnitValidator(
            $this->createMeasurementUnitValidatorMock(null),
            $this->createPackagingUnitValidatorMock($this->createError(static::GLOSSARY_KEY_PACKAGING_UNIT)),
            $this->createConfigMock(true, true),
        );

        // Act
        $errorTransfer = $addedItemProductUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_PACKAGING_UNIT, $errorTransfer->getMessage());
    }

    /**
     * A product carrying both units must report the measurement rule, which is the cheaper check and runs first.
     */
    public function testMeasurementUnitErrorWinsWhenBothRulesMatch(): void
    {
        // Arrange
        $addedItemProductPackagingUnitValidatorMock = $this->createMock(AddedItemProductPackagingUnitValidatorInterface::class);
        $addedItemProductPackagingUnitValidatorMock->expects($this->never())->method('validate');

        $addedItemProductUnitValidator = new AddedItemProductUnitValidator(
            $this->createMeasurementUnitValidatorMock($this->createError(static::GLOSSARY_KEY_MEASUREMENT_UNIT)),
            $addedItemProductPackagingUnitValidatorMock,
            $this->createConfigMock(true, true),
        );

        // Act
        $errorTransfer = $addedItemProductUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_MEASUREMENT_UNIT, $errorTransfer->getMessage());
    }

    /**
     * With the measurement flag off, a product carrying both units must fall through to the packaging rule.
     */
    public function testPackagingUnitErrorSurfacesWhenTheMeasurementFlagIsOff(): void
    {
        // Arrange
        $addedItemProductMeasurementUnitValidatorMock = $this->createMock(AddedItemProductMeasurementUnitValidatorInterface::class);
        $addedItemProductMeasurementUnitValidatorMock->expects($this->never())->method('validate');

        $addedItemProductUnitValidator = new AddedItemProductUnitValidator(
            $addedItemProductMeasurementUnitValidatorMock,
            $this->createPackagingUnitValidatorMock($this->createError(static::GLOSSARY_KEY_PACKAGING_UNIT)),
            $this->createConfigMock(false, true),
        );

        // Act
        $errorTransfer = $addedItemProductUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_PACKAGING_UNIT, $errorTransfer->getMessage());
    }

    public function testAcceptsAdditionWhenThePackagingFlagIsOffAndTheMeasurementRuleDoesNotMatch(): void
    {
        // Arrange
        $addedItemProductPackagingUnitValidatorMock = $this->createMock(AddedItemProductPackagingUnitValidatorInterface::class);
        $addedItemProductPackagingUnitValidatorMock->expects($this->never())->method('validate');

        $addedItemProductUnitValidator = new AddedItemProductUnitValidator(
            $this->createMeasurementUnitValidatorMock(null),
            $addedItemProductPackagingUnitValidatorMock,
            $this->createConfigMock(true, false),
        );

        // Act
        $errorTransfer = $addedItemProductUnitValidator->validate($this->createItemTransfers(), static::SKU);

        // Assert
        $this->assertNull($errorTransfer);
    }

    protected function createConfigMock(
        bool $isMeasurementUnitRestricted,
        bool $isPackagingUnitRestricted,
    ): OrderExperienceManagementConfig {
        $orderExperienceManagementConfigMock = $this->createMock(OrderExperienceManagementConfig::class);
        $orderExperienceManagementConfigMock
            ->method('isMeasurementUnitProductAdditionRestricted')
            ->willReturn($isMeasurementUnitRestricted);
        $orderExperienceManagementConfigMock
            ->method('isPackagingUnitProductAdditionRestricted')
            ->willReturn($isPackagingUnitRestricted);

        return $orderExperienceManagementConfigMock;
    }

    protected function createMeasurementUnitValidatorMock(?ErrorTransfer $errorTransfer): AddedItemProductMeasurementUnitValidatorInterface
    {
        $addedItemProductMeasurementUnitValidatorMock = $this->createMock(AddedItemProductMeasurementUnitValidatorInterface::class);
        $addedItemProductMeasurementUnitValidatorMock->method('validate')->willReturn($errorTransfer);

        return $addedItemProductMeasurementUnitValidatorMock;
    }

    protected function createPackagingUnitValidatorMock(?ErrorTransfer $errorTransfer): AddedItemProductPackagingUnitValidatorInterface
    {
        $addedItemProductPackagingUnitValidatorMock = $this->createMock(AddedItemProductPackagingUnitValidatorInterface::class);
        $addedItemProductPackagingUnitValidatorMock->method('validate')->willReturn($errorTransfer);

        return $addedItemProductPackagingUnitValidatorMock;
    }

    protected function createError(string $message): ErrorTransfer
    {
        return (new ErrorTransfer())->setMessage($message)->setParameters(['%sku%' => static::SKU]);
    }

    /**
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    protected function createItemTransfers(): array
    {
        return [(new ItemTransfer())->setSku(static::SKU)->setId(298)->setIdProductAbstract(215)];
    }
}
