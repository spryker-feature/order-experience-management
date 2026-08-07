<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Order;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\BundleItemClassifier;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\ItemShipmentMethodResolver;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 *  OrderExperienceManagement
 * @group Business
 * @group Order
 * @group ItemShipmentMethodResolverTest
 * Add your own group annotations below this line
 */
class ItemShipmentMethodResolverTest extends Unit
{
    protected const string MERCHANT_REFERENCE_A = 'MER000001';

    protected const string MERCHANT_REFERENCE_B = 'MER000002';

    protected function createResolver(): ItemShipmentMethodResolver
    {
        return new ItemShipmentMethodResolver(new BundleItemClassifier());
    }

    public function testApplyFallbackShipmentsCopiesShipmentFromItemWithSameMerchantReference(): void
    {
        // Arrange
        $referenceItem = (new ItemTransfer())
            ->setMerchantReference(static::MERCHANT_REFERENCE_A)
            ->setShipment($this->createShipment(1));
        $targetItem = (new ItemTransfer())->setMerchantReference(static::MERCHANT_REFERENCE_A);

        // Act
        $this->createResolver()->applyFallbackShipments([$referenceItem, $targetItem], new QuoteTransfer());

        // Assert
        $this->assertSame(1, $targetItem->getShipment()?->getMethod()?->getIdShipmentMethod());
    }

    public function testApplyFallbackShipmentsFallsBackToExpenseShipmentWhenNoItemMatches(): void
    {
        // Arrange
        $targetItem = (new ItemTransfer())->setMerchantReference(static::MERCHANT_REFERENCE_A);
        $quoteTransfer = (new QuoteTransfer())->addExpense(
            (new ExpenseTransfer())->setShipment($this->createShipment(7, static::MERCHANT_REFERENCE_A)),
        );

        // Act
        $this->createResolver()->applyFallbackShipments([$targetItem], $quoteTransfer);

        // Assert
        $this->assertSame(7, $targetItem->getShipment()?->getMethod()?->getIdShipmentMethod());
    }

    public function testApplyFallbackShipmentsPrefersItemShipmentOverExpenseShipment(): void
    {
        // Arrange
        $referenceItem = (new ItemTransfer())
            ->setMerchantReference(static::MERCHANT_REFERENCE_A)
            ->setShipment($this->createShipment(1));
        $targetItem = (new ItemTransfer())->setMerchantReference(static::MERCHANT_REFERENCE_A);
        $quoteTransfer = (new QuoteTransfer())->addExpense(
            (new ExpenseTransfer())->setShipment($this->createShipment(7, static::MERCHANT_REFERENCE_A)),
        );

        // Act
        $this->createResolver()->applyFallbackShipments([$referenceItem, $targetItem], $quoteTransfer);

        // Assert
        $this->assertSame(1, $targetItem->getShipment()?->getMethod()?->getIdShipmentMethod());
    }

    public function testApplyFallbackShipmentsResolvesPerMerchantReference(): void
    {
        // Arrange
        $referenceItemA = (new ItemTransfer())
            ->setMerchantReference(static::MERCHANT_REFERENCE_A)
            ->setShipment($this->createShipment(1));
        $referenceItemB = (new ItemTransfer())
            ->setMerchantReference(static::MERCHANT_REFERENCE_B)
            ->setShipment($this->createShipment(2));
        $targetItemB = (new ItemTransfer())->setMerchantReference(static::MERCHANT_REFERENCE_B);

        // Act
        $this->createResolver()->applyFallbackShipments(
            [$referenceItemA, $referenceItemB, $targetItemB],
            new QuoteTransfer(),
        );

        // Assert
        $this->assertSame(2, $targetItemB->getShipment()?->getMethod()?->getIdShipmentMethod());
    }

    public function testApplyFallbackShipmentsMatchesNullMerchantReference(): void
    {
        // Arrange
        $referenceItem = (new ItemTransfer())->setShipment($this->createShipment(3));
        $targetItem = (new ItemTransfer());

        // Act
        $this->createResolver()->applyFallbackShipments([$referenceItem, $targetItem], new QuoteTransfer());

        // Assert
        $this->assertSame(3, $targetItem->getShipment()?->getMethod()?->getIdShipmentMethod());
    }

    public function testApplyFallbackShipmentsLeavesExistingShipmentUntouched(): void
    {
        // Arrange
        $referenceItem = (new ItemTransfer())
            ->setMerchantReference(static::MERCHANT_REFERENCE_A)
            ->setShipment($this->createShipment(1));
        $itemWithShipment = (new ItemTransfer())
            ->setMerchantReference(static::MERCHANT_REFERENCE_A)
            ->setShipment($this->createShipment(9));

        // Act
        $this->createResolver()->applyFallbackShipments([$referenceItem, $itemWithShipment], new QuoteTransfer());

        // Assert
        $this->assertSame(9, $itemWithShipment->getShipment()?->getMethod()?->getIdShipmentMethod());
    }

    public function testApplyFallbackShipmentsLeavesShipmentNullWhenNoReferenceExists(): void
    {
        // Arrange
        $targetItem = (new ItemTransfer())->setMerchantReference(static::MERCHANT_REFERENCE_A);

        // Act
        $this->createResolver()->applyFallbackShipments([$targetItem], new QuoteTransfer());

        // Assert
        $this->assertNull($targetItem->getShipment());
    }

    protected function createShipment(int $idShipmentMethod, ?string $merchantReference = null): ShipmentTransfer
    {
        return (new ShipmentTransfer())
            ->setMerchantReference($merchantReference)
            ->setMethod((new ShipmentMethodTransfer())->setIdShipmentMethod($idShipmentMethod));
    }
}
