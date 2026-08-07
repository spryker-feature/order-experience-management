<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Schedule\Review;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Spryker\Zed\Cart\Business\CartFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemCartAdder;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Schedule
 * @group Review
 * @group AddedItemCartAdderTest
 * Add your own group annotations below this line
 */
class AddedItemCartAdderTest extends Unit
{
    /**
     * @uses \SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\AddedItemCartAdder::GROUP_KEY_PREFIX
     */
    protected const string GROUP_KEY_PREFIX_FORMAT = 'recurring-order-added-item-%d';

    protected const string SKU_BUNDLE = '213_123';

    protected const string SKU_BUNDLED = '130_24725761';

    protected const string SKU_PLAIN = '136_24425591';

    protected const string BUNDLE_ITEM_IDENTIFIER = 'recurring-order-added-item-0_213_123_16a68d534d5820';

    protected const string MERCHANT_REFERENCE = 'MER000002';

    public function testAddItemsGroupsPlainItemByItsGroupKeyPrefix(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addItem($this->createItem(static::SKU_PLAIN, 33265, $this->createGroupKeyPrefix(0)));
        $addedItemCartAdder = $this->createAddedItemCartAdder($quoteTransfer);

        // Act
        $itemTransfersByIndex = $addedItemCartAdder->addItems([$this->createAddition(static::SKU_PLAIN)], [], new QuoteTransfer());

        // Assert
        $this->assertSame([static::SKU_PLAIN], $this->extractSkus($itemTransfersByIndex[0] ?? []));
    }

    /**
     * Adding a bundle replaces the requested item with the bundled products it consists of, and only the bundle
     * keeps the requested group key prefix. Losing the bundled products leaves the addition without any priced
     * item, which makes the placeability check fail on the payment method instead of adding the bundle.
     */
    public function testAddItemsGroupsBundledItemsWithTheBundleTheyBelongTo(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addItem($this->createBundledItem(11667))
            ->addItem($this->createBundledItem(11666))
            ->addItem($this->createBundledItem(11667))
            ->addBundleItem(
                $this->createItem(static::SKU_BUNDLE, 35000, $this->createGroupKeyPrefix(0))
                    ->setBundleItemIdentifier(static::BUNDLE_ITEM_IDENTIFIER),
            );
        $addedItemCartAdder = $this->createAddedItemCartAdder($quoteTransfer);

        // Act
        $itemTransfersByIndex = $addedItemCartAdder->addItems([$this->createAddition(static::SKU_BUNDLE)], [], new QuoteTransfer());

        // Assert
        $this->assertSame(
            [static::SKU_BUNDLED, static::SKU_BUNDLED, static::SKU_BUNDLED, static::SKU_BUNDLE],
            $this->extractSkus($itemTransfersByIndex[0] ?? []),
        );
    }

    public function testAddItemsKeepsBundledItemsWithTheirOwnAdditionWhenSeveralBundlesAreAdded(): void
    {
        // Arrange
        $secondBundleItemIdentifier = 'recurring-order-added-item-1_213_123_16a68d534d5821';
        $quoteTransfer = (new QuoteTransfer())
            ->addItem($this->createBundledItem(11667))
            ->addItem($this->createBundledItem(22000)->setRelatedBundleItemIdentifier($secondBundleItemIdentifier))
            ->addBundleItem(
                $this->createItem(static::SKU_BUNDLE, 35000, $this->createGroupKeyPrefix(0))
                    ->setBundleItemIdentifier(static::BUNDLE_ITEM_IDENTIFIER),
            )
            ->addBundleItem(
                $this->createItem(static::SKU_BUNDLE, 22000, $this->createGroupKeyPrefix(1))
                    ->setBundleItemIdentifier($secondBundleItemIdentifier),
            );
        $addedItemCartAdder = $this->createAddedItemCartAdder($quoteTransfer);

        // Act
        $itemTransfersByIndex = $addedItemCartAdder->addItems(
            [$this->createAddition(static::SKU_BUNDLE), $this->createAddition(static::SKU_BUNDLE)],
            [],
            new QuoteTransfer(),
        );

        // Assert
        $this->assertSame(11667, $itemTransfersByIndex[0][0]->getUnitGrossPrice());
        $this->assertSame(22000, $itemTransfersByIndex[1][0]->getUnitGrossPrice());
    }

    public function testAddItemsIgnoresItemsThatBelongToNoAddition(): void
    {
        // Arrange
        $quoteTransfer = (new QuoteTransfer())
            ->addItem($this->createItem(static::SKU_PLAIN, 33265, null))
            ->addItem($this->createBundledItem(11667)->setRelatedBundleItemIdentifier('unknown-bundle-item'));
        $addedItemCartAdder = $this->createAddedItemCartAdder($quoteTransfer);

        // Act
        $itemTransfersByIndex = $addedItemCartAdder->addItems([$this->createAddition(static::SKU_PLAIN)], [], new QuoteTransfer());

        // Assert
        $this->assertSame([], $itemTransfersByIndex);
    }

    public function testAddItemsAppliesMerchantReferencesByAdditionIndex(): void
    {
        // Arrange
        $cartChangeTransfer = null;
        $cartFacadeMock = $this->createMock(CartFacadeInterface::class);
        $cartFacadeMock->method('add')->willReturnCallback(
            function (CartChangeTransfer $passedCartChangeTransfer) use (&$cartChangeTransfer): QuoteTransfer {
                $cartChangeTransfer = $passedCartChangeTransfer;

                return new QuoteTransfer();
            },
        );
        $addedItemCartAdder = new AddedItemCartAdder($cartFacadeMock);

        // Act
        $addedItemCartAdder->addItems(
            [$this->createAddition(static::SKU_PLAIN), $this->createAddition(static::SKU_BUNDLE)],
            [1 => static::MERCHANT_REFERENCE],
            new QuoteTransfer(),
        );

        // Assert
        $itemTransfers = $cartChangeTransfer->getItems()->getArrayCopy();
        $this->assertNull($itemTransfers[0]->getMerchantReference());
        $this->assertSame(static::MERCHANT_REFERENCE, $itemTransfers[1]->getMerchantReference());
    }

    /**
     * The schedule quote is reused for shipment resolution right after this call, so emptying its items to
     * build the cart change must not reach the caller's instance.
     */
    public function testAddItemsLeavesTheScheduleQuoteItemsUntouched(): void
    {
        // Arrange
        $scheduleQuoteTransfer = (new QuoteTransfer())
            ->addItem($this->createItem(static::SKU_PLAIN, 33265, null))
            ->addBundleItem($this->createItem(static::SKU_BUNDLE, 35000, null));
        $addedItemCartAdder = $this->createAddedItemCartAdder(new QuoteTransfer());

        // Act
        $addedItemCartAdder->addItems([$this->createAddition(static::SKU_PLAIN)], [], $scheduleQuoteTransfer);

        // Assert
        $this->assertSame([static::SKU_PLAIN], $this->extractSkus($scheduleQuoteTransfer->getItems()->getArrayCopy()));
        $this->assertSame([static::SKU_BUNDLE], $this->extractSkus($scheduleQuoteTransfer->getBundleItems()->getArrayCopy()));
    }

    public function testAddItemsSendsAnEmptyQuoteToTheCartFacade(): void
    {
        // Arrange
        $cartChangeTransfer = null;
        $cartFacadeMock = $this->createMock(CartFacadeInterface::class);
        $cartFacadeMock->method('add')->willReturnCallback(
            function (CartChangeTransfer $passedCartChangeTransfer) use (&$cartChangeTransfer): QuoteTransfer {
                $cartChangeTransfer = $passedCartChangeTransfer;

                return new QuoteTransfer();
            },
        );
        $addedItemCartAdder = new AddedItemCartAdder($cartFacadeMock);
        $scheduleQuoteTransfer = (new QuoteTransfer())
            ->setPriceMode('GROSS_MODE')
            ->addItem($this->createItem(static::SKU_PLAIN, 33265, null));

        // Act
        $addedItemCartAdder->addItems([$this->createAddition(static::SKU_BUNDLE)], [], $scheduleQuoteTransfer);

        // Assert
        $this->assertCount(0, $cartChangeTransfer->getQuoteOrFail()->getItems());
        $this->assertCount(0, $cartChangeTransfer->getQuoteOrFail()->getBundleItems());
        $this->assertSame('GROSS_MODE', $cartChangeTransfer->getQuoteOrFail()->getPriceMode());
    }

    protected function createAddedItemCartAdder(QuoteTransfer $updatedQuoteTransfer): AddedItemCartAdder
    {
        $cartFacadeMock = $this->createMock(CartFacadeInterface::class);
        $cartFacadeMock->method('add')->willReturn($updatedQuoteTransfer);

        return new AddedItemCartAdder($cartFacadeMock);
    }

    protected function createAddition(string $sku): RecurringScheduleItemAdditionTransfer
    {
        return (new RecurringScheduleItemAdditionTransfer())->setSku($sku)->setQuantity(1);
    }

    protected function createItem(string $sku, int $unitGrossPrice, ?string $groupKeyPrefix): ItemTransfer
    {
        return (new ItemTransfer())
            ->setSku($sku)
            ->setQuantity(1)
            ->setUnitGrossPrice($unitGrossPrice)
            ->setGroupKeyPrefix($groupKeyPrefix);
    }

    protected function createBundledItem(int $unitGrossPrice): ItemTransfer
    {
        return $this->createItem(static::SKU_BUNDLED, $unitGrossPrice, null)
            ->setRelatedBundleItemIdentifier(static::BUNDLE_ITEM_IDENTIFIER);
    }

    protected function createGroupKeyPrefix(int $index): string
    {
        return sprintf(static::GROUP_KEY_PREFIX_FORMAT, $index);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string>
     */
    protected function extractSkus(array $itemTransfers): array
    {
        return array_map(static fn (ItemTransfer $itemTransfer): string => $itemTransfer->getSkuOrFail(), $itemTransfers);
    }
}
