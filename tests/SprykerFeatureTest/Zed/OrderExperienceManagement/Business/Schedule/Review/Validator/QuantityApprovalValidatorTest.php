<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator\QuantityApprovalValidator;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group Validator
 * @group QuantityApprovalValidatorTest
 * Add your own group annotations below this line
 */
class QuantityApprovalValidatorTest extends Unit
{
    protected const string GLOSSARY_KEY_QUANTITY_INVALID = 'recurring_orders.review.quantity_invalid';

    protected const string SKU = 'test-sku';

    /**
     * @return array<string, array{int}>
     */
    public function invalidAcceptedQuantityDataProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-5],
        ];
    }

    /**
     * @dataProvider invalidAcceptedQuantityDataProvider
     */
    public function testReturnsErrorWhenAcceptedQuantityIsBelowOne(int $acceptedQuantity): void
    {
        // Arrange
        $recurringScheduleEventRequestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->addAcceptedItem((new RecurringScheduleItemReviewTransfer())->setAcceptedQuantity($acceptedQuantity));

        // Act
        $errorTransfer = (new QuantityApprovalValidator())->validate(
            $recurringScheduleEventRequestTransfer,
            new RecurringScheduleReviewResponseTransfer(),
        );

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_QUANTITY_INVALID, $errorTransfer->getMessage());
    }

    public function testReturnsNullWhenAcceptedQuantityIsOne(): void
    {
        // Arrange
        $recurringScheduleEventRequestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->addAcceptedItem((new RecurringScheduleItemReviewTransfer())->setAcceptedQuantity(1));

        // Act & Assert
        $this->assertNull($this->validate($recurringScheduleEventRequestTransfer));
    }

    public function testReturnsNullWhenAcceptedQuantityIsNullAsTheLineIsUnchanged(): void
    {
        // Arrange
        $recurringScheduleEventRequestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->addAcceptedItem((new RecurringScheduleItemReviewTransfer())->setAcceptedQuantity(null));

        // Act & Assert
        $this->assertNull($this->validate($recurringScheduleEventRequestTransfer));
    }

    public function testReturnsErrorWhenOnlyOneOfSeveralAcceptedItemsIsInvalid(): void
    {
        // Arrange
        $recurringScheduleEventRequestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->addAcceptedItem((new RecurringScheduleItemReviewTransfer())->setAcceptedQuantity(3))
            ->addAcceptedItem((new RecurringScheduleItemReviewTransfer())->setAcceptedQuantity(0))
            ->addAcceptedItem((new RecurringScheduleItemReviewTransfer())->setAcceptedQuantity(2));

        // Act
        $errorTransfer = $this->validate($recurringScheduleEventRequestTransfer);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_QUANTITY_INVALID, $errorTransfer->getMessage());
    }

    /**
     * @dataProvider invalidAcceptedQuantityDataProvider
     */
    public function testReturnsErrorWhenAddedItemQuantityIsBelowOne(int $quantity): void
    {
        // Arrange
        $recurringScheduleEventRequestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->addAddedItem(
                (new RecurringScheduleItemAdditionTransfer())->setSku(static::SKU)->setQuantity($quantity),
            );

        // Act
        $errorTransfer = $this->validate($recurringScheduleEventRequestTransfer);

        // Assert
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_QUANTITY_INVALID, $errorTransfer->getMessage());
    }

    public function testReturnsErrorWhenAddedItemQuantityIsMissing(): void
    {
        // Arrange
        $recurringScheduleEventRequestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->addAddedItem((new RecurringScheduleItemAdditionTransfer())->setSku(static::SKU));

        // Act
        $errorTransfer = $this->validate($recurringScheduleEventRequestTransfer);

        // Assert - an added item always carries an explicit quantity, so a missing one is invalid.
        $this->assertNotNull($errorTransfer);
        $this->assertSame(static::GLOSSARY_KEY_QUANTITY_INVALID, $errorTransfer->getMessage());
    }

    public function testReturnsNullWhenAddedItemQuantityIsPositive(): void
    {
        // Arrange
        $recurringScheduleEventRequestTransfer = (new RecurringScheduleEventRequestTransfer())
            ->addAddedItem((new RecurringScheduleItemAdditionTransfer())->setSku(static::SKU)->setQuantity(2));

        // Act & Assert
        $this->assertNull($this->validate($recurringScheduleEventRequestTransfer));
    }

    public function testReturnsNullWhenRequestCarriesNoItems(): void
    {
        // Act & Assert
        $this->assertNull($this->validate(new RecurringScheduleEventRequestTransfer()));
    }

    protected function validate(RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer): ?object
    {
        return (new QuantityApprovalValidator())->validate(
            $recurringScheduleEventRequestTransfer,
            new RecurringScheduleReviewResponseTransfer(),
        );
    }
}
