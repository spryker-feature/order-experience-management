<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

use ArrayObject;
use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\Calculation\Business\CalculationFacadeInterface;
use Spryker\Zed\Checkout\Business\CheckoutFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\BundleItemClassifierInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Order\PlaceableQuoteDeserializerInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator\AddedItemProductUnitValidatorInterface;

class ScheduleReviewItemAdditionValidator implements ScheduleReviewItemAdditionValidatorInterface
{
    protected const string GLOSSARY_KEY_NO_PRICE = 'recurring_orders.review.add_product.error.no_price';

    protected const string GLOSSARY_KEY_NOT_AVAILABLE = 'recurring_orders.review.add_product.error.not_available';

    protected const string GLOSSARY_KEY_SHIPMENT_UNAVAILABLE = 'recurring_orders.review.add_product.error.shipment_unavailable';

    protected const string GLOSSARY_KEY_NOT_PLACEABLE = 'recurring_orders.review.add_product.error.not_placeable';

    protected const string PARAMETER_SKU = '%sku%';

    /**
     * @param array<\SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\AddedItemValidatorPluginInterface> $addedItemValidatorPlugins
     */
    public function __construct(
        protected readonly PlaceableQuoteDeserializerInterface $placeableQuoteDeserializer,
        protected readonly CheckoutFacadeInterface $checkoutFacade,
        protected readonly BundleItemClassifierInterface $bundleItemClassifier,
        protected readonly CalculationFacadeInterface $calculationFacade,
        protected readonly AddedItemProductUnitValidatorInterface $addedItemProductUnitValidator,
        protected readonly array $addedItemValidatorPlugins,
    ) {
    }

    /**
     * @param array<\Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     * @param array<int, array<\Generated\Shared\Transfer\ItemTransfer>> $itemTransfersByIndex
     */
    public function validate(
        array $recurringScheduleItemAdditionTransfers,
        array $itemTransfersByIndex,
        RecurringScheduleTransfer $recurringScheduleTransfer,
    ): ?ErrorTransfer {
        if ($recurringScheduleItemAdditionTransfers === []) {
            return null;
        }

        $itemTransfers = [];

        foreach ($recurringScheduleItemAdditionTransfers as $index => $recurringScheduleItemAdditionTransfer) {
            $resolvedItemTransfers = $itemTransfersByIndex[$index] ?? [];

            $errorTransfer = $this->validateResolvedItems($resolvedItemTransfers, $recurringScheduleItemAdditionTransfer);

            if ($errorTransfer !== null) {
                return $errorTransfer;
            }

            $itemTransfers = array_merge($itemTransfers, $resolvedItemTransfers);
        }

        return $this->validatePlaceability($itemTransfers, $recurringScheduleTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $resolvedItemTransfers
     */
    protected function validateResolvedItems(
        array $resolvedItemTransfers,
        RecurringScheduleItemAdditionTransfer $recurringScheduleItemAdditionTransfer,
    ): ?ErrorTransfer {
        $sku = $recurringScheduleItemAdditionTransfer->getSkuOrFail();

        if ($resolvedItemTransfers === []) {
            return $this->createError(static::GLOSSARY_KEY_NOT_AVAILABLE, $sku);
        }

        if (!$this->hasResolvedPrice($resolvedItemTransfers)) {
            return $this->createError(static::GLOSSARY_KEY_NO_PRICE, $sku);
        }

        if (!$this->hasResolvedShipment($resolvedItemTransfers)) {
            return $this->createError(static::GLOSSARY_KEY_SHIPMENT_UNAVAILABLE, $sku);
        }

        return $this->addedItemProductUnitValidator->validate($resolvedItemTransfers, $sku)
            ?? $this->validateWithPlugins($recurringScheduleItemAdditionTransfer, $resolvedItemTransfers);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $resolvedItemTransfers
     */
    protected function validateWithPlugins(
        RecurringScheduleItemAdditionTransfer $recurringScheduleItemAdditionTransfer,
        array $resolvedItemTransfers,
    ): ?ErrorTransfer {
        foreach ($this->addedItemValidatorPlugins as $addedItemValidatorPlugin) {
            $errorTransfer = $addedItemValidatorPlugin->validate($recurringScheduleItemAdditionTransfer, $resolvedItemTransfers);

            if ($errorTransfer !== null) {
                return $errorTransfer;
            }
        }

        return null;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function validatePlaceability(array $itemTransfers, RecurringScheduleTransfer $recurringScheduleTransfer): ?ErrorTransfer
    {
        $quoteTransfer = $this->buildPlaceableQuote($itemTransfers, $recurringScheduleTransfer);
        $quoteTransfer = $this->calculationFacade->recalculateQuote($quoteTransfer, false);
        $checkoutResponseTransfer = $this->checkoutFacade->isPlaceableOrder($quoteTransfer);

        if ($checkoutResponseTransfer->getIsSuccess()) {
            return null;
        }

        foreach ($checkoutResponseTransfer->getErrors() as $checkoutErrorTransfer) {
            return (new ErrorTransfer())
                ->setMessage($checkoutErrorTransfer->getMessageOrFail())
                ->setParameters($checkoutErrorTransfer->getParameters());
        }

        return (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_NOT_PLACEABLE);
    }

    protected function createError(string $message, string $sku): ErrorTransfer
    {
        return (new ErrorTransfer())
            ->setMessage($message)
            ->setParameters([static::PARAMETER_SKU => $sku]);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function buildPlaceableQuote(array $itemTransfers, RecurringScheduleTransfer $recurringScheduleTransfer): QuoteTransfer
    {
        $quoteTransfer = $this->placeableQuoteDeserializer->deserialize($recurringScheduleTransfer->getQuoteDataOrFail());
        $quoteTransfer->setItems(new ArrayObject())->setBundleItems(new ArrayObject());

        foreach ($itemTransfers as $itemTransfer) {
            $this->assignItemToQuote($quoteTransfer, $itemTransfer);
        }

        return $quoteTransfer;
    }

    protected function assignItemToQuote(QuoteTransfer $quoteTransfer, ItemTransfer $itemTransfer): void
    {
        if ($this->bundleItemClassifier->isBundleItem($itemTransfer)) {
            $quoteTransfer->addBundleItem($itemTransfer);

            return;
        }

        $quoteTransfer->addItem($itemTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function hasResolvedPrice(array $itemTransfers): bool
    {
        foreach ($itemTransfers as $itemTransfer) {
            if ((int)$itemTransfer->getUnitGrossPrice() > 0 || (int)$itemTransfer->getUnitNetPrice() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    protected function hasResolvedShipment(array $itemTransfers): bool
    {
        foreach ($itemTransfers as $itemTransfer) {
            if ($itemTransfer->getShipment()?->getMethod()?->getIdShipmentMethod() === null) {
                return false;
            }
        }

        return true;
    }
}
