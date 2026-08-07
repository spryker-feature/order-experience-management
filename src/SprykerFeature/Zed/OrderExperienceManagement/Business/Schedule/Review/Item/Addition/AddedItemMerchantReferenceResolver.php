<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition;

use Generated\Shared\Transfer\ProductOfferCollectionTransfer;
use Generated\Shared\Transfer\ProductOfferConditionsTransfer;
use Generated\Shared\Transfer\ProductOfferCriteriaTransfer;
use Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer;
use Spryker\Shared\ProductOffer\ProductOfferConfig as SharedProductOfferConfig;
use Spryker\Zed\MerchantProduct\Business\MerchantProductFacadeInterface;
use Spryker\Zed\ProductOffer\Business\ProductOfferFacadeInterface;

class AddedItemMerchantReferenceResolver implements AddedItemMerchantReferenceResolverInterface
{
    public function __construct(
        protected readonly ProductOfferFacadeInterface $productOfferFacade,
        protected readonly MerchantProductFacadeInterface $merchantProductFacade,
    ) {
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<int, string>
     */
    public function resolveMerchantReferences(array $recurringScheduleItemAdditionTransfers): array
    {
        $merchantReferencesByProductOfferReference = $this->mapMerchantReferencesByProductOfferReference(
            $recurringScheduleItemAdditionTransfers,
        );
        $merchantReferencesBySku = $this->mapMerchantReferencesBySku($recurringScheduleItemAdditionTransfers);

        $merchantReferencesByIndex = [];

        foreach ($recurringScheduleItemAdditionTransfers as $index => $recurringScheduleItemAdditionTransfer) {
            $merchantReference = $this->findMerchantReference(
                $recurringScheduleItemAdditionTransfer,
                $merchantReferencesByProductOfferReference,
                $merchantReferencesBySku,
            );

            if ($merchantReference !== null) {
                $merchantReferencesByIndex[$index] = $merchantReference;
            }
        }

        return $merchantReferencesByIndex;
    }

    /**
     * @param array<string, string> $merchantReferencesByProductOfferReference
     * @param array<string, string> $merchantReferencesBySku
     */
    protected function findMerchantReference(
        RecurringScheduleItemAdditionTransfer $recurringScheduleItemAdditionTransfer,
        array $merchantReferencesByProductOfferReference,
        array $merchantReferencesBySku,
    ): ?string {
        $productOfferReference = $recurringScheduleItemAdditionTransfer->getProductOfferReference();

        if ($productOfferReference !== null && $productOfferReference !== '') {
            return $merchantReferencesByProductOfferReference[$productOfferReference] ?? null;
        }

        return $merchantReferencesBySku[$recurringScheduleItemAdditionTransfer->getSkuOrFail()] ?? null;
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<string, string>
     */
    protected function mapMerchantReferencesByProductOfferReference(array $recurringScheduleItemAdditionTransfers): array
    {
        $productOfferReferences = $this->extractProductOfferReferences($recurringScheduleItemAdditionTransfers);

        if ($productOfferReferences === []) {
            return [];
        }

        $productOfferCollectionTransfer = $this->productOfferFacade->getProductOfferCollection(
            (new ProductOfferCriteriaTransfer())->setProductOfferConditions(
                (new ProductOfferConditionsTransfer())->setProductOfferReferences($productOfferReferences),
            ),
        );

        return $this->extractApprovedOfferMerchantReferences($productOfferCollectionTransfer);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<string, string>
     */
    protected function mapMerchantReferencesBySku(array $recurringScheduleItemAdditionTransfers): array
    {
        $skus = $this->extractSkusWithoutProductOfferReference($recurringScheduleItemAdditionTransfers);

        if ($skus === []) {
            return [];
        }

        return $this->merchantProductFacade->getConcreteProductSkuMerchantReferenceMap($skus);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<int, string>
     */
    protected function extractProductOfferReferences(array $recurringScheduleItemAdditionTransfers): array
    {
        $productOfferReferences = [];

        foreach ($recurringScheduleItemAdditionTransfers as $recurringScheduleItemAdditionTransfer) {
            $productOfferReference = $recurringScheduleItemAdditionTransfer->getProductOfferReference();

            if ($productOfferReference !== null && $productOfferReference !== '') {
                $productOfferReferences[$productOfferReference] = $productOfferReference;
            }
        }

        return array_values($productOfferReferences);
    }

    /**
     * @param array<int, \Generated\Shared\Transfer\RecurringScheduleItemAdditionTransfer> $recurringScheduleItemAdditionTransfers
     *
     * @return array<int, string>
     */
    protected function extractSkusWithoutProductOfferReference(array $recurringScheduleItemAdditionTransfers): array
    {
        $skus = [];

        foreach ($recurringScheduleItemAdditionTransfers as $recurringScheduleItemAdditionTransfer) {
            $productOfferReference = $recurringScheduleItemAdditionTransfer->getProductOfferReference();

            if ($productOfferReference !== null && $productOfferReference !== '') {
                continue;
            }

            $sku = $recurringScheduleItemAdditionTransfer->getSkuOrFail();
            $skus[$sku] = $sku;
        }

        return array_values($skus);
    }

    /**
     * @return array<string, string>
     */
    protected function extractApprovedOfferMerchantReferences(ProductOfferCollectionTransfer $productOfferCollectionTransfer): array
    {
        $merchantReferenceMap = [];

        foreach ($productOfferCollectionTransfer->getProductOffers() as $productOfferTransfer) {
            $merchantReference = $productOfferTransfer->getMerchantReference();

            if (
                $merchantReference === null
                || $productOfferTransfer->getIsActive() !== true
                || $productOfferTransfer->getApprovalStatus() !== SharedProductOfferConfig::STATUS_APPROVED
            ) {
                continue;
            }

            $merchantReferenceMap[$productOfferTransfer->getProductOfferReferenceOrFail()] = $merchantReference;
        }

        return $merchantReferenceMap;
    }
}
