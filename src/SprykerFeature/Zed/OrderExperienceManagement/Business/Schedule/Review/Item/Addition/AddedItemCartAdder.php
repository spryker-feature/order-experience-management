<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

use ArrayObject;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\Cart\Business\CartFacadeInterface;

class AddedItemCartAdder implements AddedItemCartAdderInterface
{
    /**
     * @uses \Spryker\Zed\Cart\Communication\Plugin\SkuGroupKeyPlugin
     */
    protected const string GROUP_KEY_PREFIX = 'recurring-order-added-item';

    public function __construct(
        protected readonly CartFacadeInterface $cartFacade,
    ) {
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     * @param array<int, string> $merchantReferenceMap
     *
     * @return array<int, array<\Generated\Shared\Transfer\ItemTransfer>>
     */
    public function addItems(
        array $recurringScheduleItemAdditionTransfers,
        array $merchantReferenceMap,
        QuoteTransfer $scheduleQuoteTransfer,
    ): array {
        $cartChangeTransfer = $this->createCartChange($recurringScheduleItemAdditionTransfers, $merchantReferenceMap, $scheduleQuoteTransfer);
        $updatedQuoteTransfer = $this->cartFacade->add($cartChangeTransfer);

        $itemTransfers = array_merge(
            $updatedQuoteTransfer->getItems()->getArrayCopy(),
            $updatedQuoteTransfer->getBundleItems()->getArrayCopy(),
        );

        return $this->groupItemsByAdditionIndex($itemTransfers, $recurringScheduleItemAdditionTransfers);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     * @param array<int, string> $merchantReferenceMap
     */
    protected function createCartChange(
        array $recurringScheduleItemAdditionTransfers,
        array $merchantReferenceMap,
        QuoteTransfer $scheduleQuoteTransfer,
    ): CartChangeTransfer {
        $quoteTransfer = (new QuoteTransfer())
            ->fromArray($scheduleQuoteTransfer->toArray(true, true), true)
            ->setItems(new ArrayObject())
            ->setBundleItems(new ArrayObject());

        $cartChangeTransfer = (new CartChangeTransfer())->setQuote($quoteTransfer);

        foreach ($recurringScheduleItemAdditionTransfers as $index => $recurringScheduleItemAdditionTransfer) {
            $merchantReference = $merchantReferenceMap[$index] ?? null;

            $cartChangeTransfer->addItem(
                (new ItemTransfer())
                    ->setSku($recurringScheduleItemAdditionTransfer->getSkuOrFail())
                    ->setQuantity($recurringScheduleItemAdditionTransfer->getQuantityOrFail())
                    ->setProductOfferReference($recurringScheduleItemAdditionTransfer->getProductOfferReference())
                    ->setMerchantReference($merchantReference)
                    ->setGroupKeyPrefix($this->createGroupKeyPrefix($index)),
            );
        }

        return $cartChangeTransfer;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<int, array<\Generated\Shared\Transfer\ItemTransfer>>
     */
    protected function groupItemsByAdditionIndex(array $itemTransfers, array $recurringScheduleItemAdditionTransfers): array
    {
        $indexByGroupKeyPrefix = [];

        foreach (array_keys($recurringScheduleItemAdditionTransfers) as $index) {
            $indexByGroupKeyPrefix[$this->createGroupKeyPrefix($index)] = $index;
        }

        $indexByBundleItemIdentifier = $this->mapAdditionIndexesByBundleItemIdentifier($itemTransfers, $indexByGroupKeyPrefix);

        $itemTransfersByIndex = [];

        foreach ($itemTransfers as $itemTransfer) {
            $index = $this->resolveAdditionIndex($itemTransfer, $indexByGroupKeyPrefix, $indexByBundleItemIdentifier);

            if ($index === null) {
                continue;
            }

            $itemTransfersByIndex[$index][] = $itemTransfer;
        }

        return $itemTransfersByIndex;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     * @param array<string, int> $indexByGroupKeyPrefix
     *
     * @return array<string, int>
     */
    protected function mapAdditionIndexesByBundleItemIdentifier(array $itemTransfers, array $indexByGroupKeyPrefix): array
    {
        $indexByBundleItemIdentifier = [];

        foreach ($itemTransfers as $itemTransfer) {
            $bundleItemIdentifier = $itemTransfer->getBundleItemIdentifier();
            $index = $indexByGroupKeyPrefix[(string)$itemTransfer->getGroupKeyPrefix()] ?? null;

            if ($bundleItemIdentifier === null || $index === null) {
                continue;
            }

            $indexByBundleItemIdentifier[$bundleItemIdentifier] = $index;
        }

        return $indexByBundleItemIdentifier;
    }

    /**
     * @param array<string, int> $indexByGroupKeyPrefix
     * @param array<string, int> $indexByBundleItemIdentifier
     */
    protected function resolveAdditionIndex(
        ItemTransfer $itemTransfer,
        array $indexByGroupKeyPrefix,
        array $indexByBundleItemIdentifier,
    ): ?int {
        $index = $indexByGroupKeyPrefix[(string)$itemTransfer->getGroupKeyPrefix()] ?? null;

        if ($index !== null) {
            return $index;
        }

        return $indexByBundleItemIdentifier[(string)$itemTransfer->getRelatedBundleItemIdentifier()] ?? null;
    }

    protected function createGroupKeyPrefix(int $index): string
    {
        return sprintf('%s-%d', static::GROUP_KEY_PREFIX, $index);
    }
}
