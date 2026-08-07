<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Order;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;

class ItemShipmentMethodResolver implements ItemShipmentMethodResolverInterface
{
    protected const string MERCHANT_REFERENCE_NULL_KEY = 'null';

    protected const string MERCHANT_REFERENCE_STRING_PREFIX = 'ref:';

    public function __construct(protected BundleItemClassifierInterface $bundleItemClassifier)
    {
    }

    public function buildShipmentMethodIdMapByMerchantReference(QuoteTransfer $quoteTransfer): array
    {
        $map = [];

        foreach ($quoteTransfer->getExpenses() as $expenseTransfer) {
            $expenseShipment = $expenseTransfer->getShipment();

            if ($expenseShipment?->getMethod()?->getIdShipmentMethod() === null) {
                continue;
            }

            $key = $expenseShipment->getMerchantReference() ?? '';
            $map[$key] = $expenseShipment->getMethod()->getIdShipmentMethod();
        }

        return $map;
    }

    public function applyShipmentMethodId(ItemTransfer $itemTransfer, array $shipmentMethodIdMap): void
    {
        $shipmentTransfer = $itemTransfer->getShipment();

        if ($shipmentTransfer?->getMethod() === null) {
            return;
        }

        if ($shipmentTransfer->getMethod()->getIdShipmentMethod() !== null) {
            return;
        }

        $key = $shipmentTransfer->getMerchantReference() ?? '';

        if (!array_key_exists($key, $shipmentMethodIdMap)) {
            return;
        }

        $shipmentTransfer->getMethod()->setIdShipmentMethod($shipmentMethodIdMap[$key]);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function applyFallbackShipments(array $itemTransfers, QuoteTransfer $quoteTransfer): array
    {
        $referenceShipmentsByMerchantReferenceKey = $this->indexReferenceShipments($itemTransfers, $quoteTransfer);

        foreach ($itemTransfers as $itemTransfer) {
            if ($itemTransfer->getShipment() !== null) {
                continue;
            }

            $referenceShipmentTransfer = $referenceShipmentsByMerchantReferenceKey[$this->buildMerchantReferenceKey($itemTransfer->getMerchantReference())] ?? null;

            if ($referenceShipmentTransfer === null) {
                continue;
            }

            $itemTransfer->setShipment(
                (new ShipmentTransfer())->fromArray($referenceShipmentTransfer->toArray(true, true), true),
            );
        }

        return $itemTransfers;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\ShipmentTransfer>
     */
    protected function indexReferenceShipments(array $itemTransfers, QuoteTransfer $quoteTransfer): array
    {
        $expenseShipmentsByMerchantReferenceKey = [];

        foreach ($quoteTransfer->getExpenses() as $expenseTransfer) {
            $expenseShipmentTransfer = $expenseTransfer->getShipment();

            if ($expenseShipmentTransfer === null) {
                continue;
            }

            $expenseShipmentsByMerchantReferenceKey[$this->buildMerchantReferenceKey($expenseShipmentTransfer->getMerchantReference())] ??= $expenseShipmentTransfer;
        }

        $itemShipmentsByMerchantReferenceKey = [];

        foreach ($itemTransfers as $itemTransfer) {
            if ($itemTransfer->getShipment() === null) {
                continue;
            }

            $itemShipmentsByMerchantReferenceKey[$this->buildMerchantReferenceKey($itemTransfer->getMerchantReference())] ??= $itemTransfer->getShipment();
        }

        return $itemShipmentsByMerchantReferenceKey + $expenseShipmentsByMerchantReferenceKey;
    }

    protected function buildMerchantReferenceKey(?string $merchantReference): string
    {
        if ($merchantReference === null) {
            return static::MERCHANT_REFERENCE_NULL_KEY;
        }

        return static::MERCHANT_REFERENCE_STRING_PREFIX . $merchantReference;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<\Generated\Shared\Transfer\ItemTransfer>
     */
    public function alignBundleShipments(array $itemTransfers): array
    {
        $childShipmentByBundleIdentifier = $this->indexChildShipments($itemTransfers);

        foreach ($itemTransfers as $itemTransfer) {
            if (!$this->bundleItemClassifier->isBundleItem($itemTransfer)) {
                continue;
            }

            $childShipmentTransfer = $childShipmentByBundleIdentifier[$itemTransfer->getBundleItemIdentifier()] ?? null;

            if ($childShipmentTransfer === null) {
                continue;
            }

            $itemTransfer->setShipment(
                (new ShipmentTransfer())->fromArray($childShipmentTransfer->toArray(true, true), true),
            );
        }

        return $itemTransfers;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\ShipmentTransfer>
     */
    protected function indexChildShipments(array $itemTransfers): array
    {
        $childShipmentByBundleIdentifier = [];

        foreach ($itemTransfers as $itemTransfer) {
            $relatedBundleItemIdentifier = $itemTransfer->getRelatedBundleItemIdentifier();
            $shipmentTransfer = $itemTransfer->getShipment();

            if ($relatedBundleItemIdentifier === null || $shipmentTransfer === null) {
                continue;
            }

            $childShipmentByBundleIdentifier[$relatedBundleItemIdentifier] ??= $shipmentTransfer;
        }

        return $childShipmentByBundleIdentifier;
    }
}
