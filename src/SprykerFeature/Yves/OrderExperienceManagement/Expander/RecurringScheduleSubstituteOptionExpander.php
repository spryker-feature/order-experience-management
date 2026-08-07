<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Expander;

use Generated\Shared\Transfer\ConcreteAlternativeProductCollectionTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductConditionsTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductCriteriaTransfer;
use Generated\Shared\Transfer\ConcreteAlternativeProductTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;
use Generated\Shared\Transfer\RecurringScheduleSubstituteOptionTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductAlternativeStorage\ProductAlternativeStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class RecurringScheduleSubstituteOptionExpander implements RecurringScheduleSubstituteOptionExpanderInterface
{
    protected const string PRICE_DELTA_SAME = 'recurring_orders.review.substitute.delta_same';

    protected const string PRICE_DELTA_LOWER = 'recurring_orders.review.substitute.delta_lower';

    protected const string PRICE_DELTA_HIGHER = 'recurring_orders.review.substitute.delta_higher';

    public function __construct(
        protected ProductAlternativeStorageClientInterface $productAlternativeStorageClient,
        protected LocaleClientInterface $localeClient,
        protected OrderExperienceManagementConfig $config,
    ) {
    }

    public function expandWithSubstituteOptions(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): RecurringScheduleReviewResponseTransfer {
        $substitutableItemReviewTransfers = $this->getSubstitutableItemReviewTransfersIndexedByGroupKey($recurringScheduleReviewResponseTransfer);

        if ($substitutableItemReviewTransfers === []) {
            return $recurringScheduleReviewResponseTransfer;
        }

        $concreteAlternativeProductTransfers = $this->getConcreteAlternativeProductTransfersIndexedBySku(
            $this->productAlternativeStorageClient->getConcreteAlternativeProductCollection(
                $this->createConcreteAlternativeProductCriteria($this->extractSkus($substitutableItemReviewTransfers)),
            ),
        );

        foreach ($substitutableItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            $sku = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getSkuOrFail();
            $concreteAlternativeProductTransfer = $concreteAlternativeProductTransfers[$sku] ?? null;

            if ($concreteAlternativeProductTransfer === null) {
                continue;
            }

            $this->addSubstituteOptions($recurringScheduleItemReviewTransfer, $concreteAlternativeProductTransfer);
        }

        return $recurringScheduleReviewResponseTransfer;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> Keys are recurring schedule item group keys.
     */
    protected function getSubstitutableItemReviewTransfersIndexedByGroupKey(
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): array {
        $substitutableItemReviewTransfers = [];

        foreach ($recurringScheduleReviewResponseTransfer->getFlaggedItems() as $recurringScheduleItemReviewTransfer) {
            $recurringScheduleItemTransfer = $recurringScheduleItemReviewTransfer->getRecurringScheduleItem();

            if ($recurringScheduleItemTransfer === null || !$this->isSubstitutable($recurringScheduleItemReviewTransfer)) {
                continue;
            }

            $groupKey = $recurringScheduleItemTransfer->getGroupKey();

            if ($groupKey === null || $recurringScheduleItemTransfer->getSku() === null) {
                continue;
            }

            $substitutableItemReviewTransfers[$groupKey] = $recurringScheduleItemReviewTransfer;
        }

        return $substitutableItemReviewTransfers;
    }

    /**
     * @return array<string, \Generated\Shared\Transfer\ConcreteAlternativeProductTransfer> Keys are concrete product SKUs.
     */
    protected function getConcreteAlternativeProductTransfersIndexedBySku(
        ConcreteAlternativeProductCollectionTransfer $concreteAlternativeProductCollectionTransfer,
    ): array {
        $concreteAlternativeProductTransfers = [];

        foreach ($concreteAlternativeProductCollectionTransfer->getConcreteAlternativeProducts() as $concreteAlternativeProductTransfer) {
            $concreteAlternativeProductTransfers[$concreteAlternativeProductTransfer->getSkuOrFail()] = $concreteAlternativeProductTransfer;
        }

        return $concreteAlternativeProductTransfers;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\RecurringScheduleItemReviewTransfer> $recurringScheduleItemReviewTransfers
     *
     * @return array<int, string>
     */
    protected function extractSkus(array $recurringScheduleItemReviewTransfers): array
    {
        $skus = [];

        foreach ($recurringScheduleItemReviewTransfers as $recurringScheduleItemReviewTransfer) {
            $skus[] = $recurringScheduleItemReviewTransfer->getRecurringScheduleItemOrFail()->getSkuOrFail();
        }

        return array_values(array_unique($skus));
    }

    /**
     * @param array<string> $skus
     */
    protected function createConcreteAlternativeProductCriteria(array $skus): ConcreteAlternativeProductCriteriaTransfer
    {
        $concreteAlternativeProductConditionsTransfer = (new ConcreteAlternativeProductConditionsTransfer())
            ->setSkus(array_map('strval', $skus))
            ->setLocaleName($this->localeClient->getCurrentLocale());

        return (new ConcreteAlternativeProductCriteriaTransfer())
            ->setConcreteAlternativeProductConditions($concreteAlternativeProductConditionsTransfer);
    }

    protected function isSubstitutable(RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer): bool
    {
        $reviewReasons = $recurringScheduleItemReviewTransfer->getReviewReasons();

        return array_intersect($reviewReasons, $this->config->getSubstitutableReviewReasons()) !== [];
    }

    protected function addSubstituteOptions(
        RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer,
        ConcreteAlternativeProductTransfer $concreteAlternativeProductTransfer,
    ): void {
        $referencePrice = $this->resolveReferencePrice($recurringScheduleItemReviewTransfer);
        $substituteOptionTransfers = $this->mapAlternativesToSubstituteOptions(
            $concreteAlternativeProductTransfer->getAlternativeProducts()->getArrayCopy(),
            $referencePrice,
        );

        foreach ($substituteOptionTransfers as $substituteOptionTransfer) {
            $recurringScheduleItemReviewTransfer->addSubstituteOption($substituteOptionTransfer);
        }
    }

    protected function resolveReferencePrice(RecurringScheduleItemReviewTransfer $recurringScheduleItemReviewTransfer): ?int
    {
        return $recurringScheduleItemReviewTransfer->getCurrentPrice()
            ?? $recurringScheduleItemReviewTransfer->getRecurringScheduleItem()?->getReferenceGrossPrice();
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductViewTransfer> $alternativeProductViewTransfers
     *
     * @return array<\Generated\Shared\Transfer\RecurringScheduleSubstituteOptionTransfer>
     */
    protected function mapAlternativesToSubstituteOptions(array $alternativeProductViewTransfers, ?int $referencePrice): array
    {
        $substituteOptionTransfers = [];

        foreach ($alternativeProductViewTransfers as $productViewTransfer) {
            $substituteOptionTransfers[] = (new RecurringScheduleSubstituteOptionTransfer())
                ->setSku($productViewTransfer->getSku())
                ->setProductName($productViewTransfer->getName())
                ->setPrice($productViewTransfer->getPrice())
                ->setIsAvailable((bool)$productViewTransfer->getAvailable())
                ->setPriceDeltaLabel($this->resolvePriceDeltaLabel($productViewTransfer->getPrice(), $referencePrice));
        }

        return $substituteOptionTransfers;
    }

    protected function resolvePriceDeltaLabel(?int $price, ?int $referencePrice): ?string
    {
        if ($price === null || $referencePrice === null) {
            return null;
        }

        if ($price === $referencePrice) {
            return static::PRICE_DELTA_SAME;
        }

        return $price < $referencePrice ? static::PRICE_DELTA_LOWER : static::PRICE_DELTA_HIGHER;
    }
}
