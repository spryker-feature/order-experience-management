<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\MerchantStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\ProductOfferStorage\ProductOfferStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductConcreteRestrictionCheckerInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductOfferAvailabilityFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class AddedProductOfferReader implements AddedProductOfferReaderInterface
{
    public function __construct(
        protected ProductOfferStorageClientInterface $productOfferStorageClient,
        protected MerchantStorageClientInterface $merchantStorageClient,
        protected ProductOfferAvailabilityFilterInterface $productOfferAvailabilityFilter,
        protected AddedMerchantProductReaderInterface $addedMerchantProductReader,
        protected OrderExperienceManagementConfig $config,
        protected AddedProductConcreteRestrictionCheckerInterface $addedProductConcreteRestrictionChecker,
    ) {
    }

    /**
     * @return array<\Generated\Shared\Transfer\ProductOfferTransfer>
     */
    public function getAvailableProductOfferChoices(string $sku): array
    {
        if ($sku === '') {
            return [];
        }

        if ($this->addedProductConcreteRestrictionChecker->isProductConcreteRestricted($sku)) {
            return [];
        }

        $productOfferTransfers = $this->getAvailableOfferChoices($sku);

        $merchantProductChoiceTransfer = $this->addedMerchantProductReader->findMerchantProductChoice($sku);

        if ($merchantProductChoiceTransfer !== null) {
            array_unshift($productOfferTransfers, $merchantProductChoiceTransfer);
        }

        return $productOfferTransfers;
    }

    /**
     * @return array<\Generated\Shared\Transfer\ProductOfferTransfer>
     */
    protected function getAvailableOfferChoices(string $sku): array
    {
        $productOfferStorageTransfers = $this->getProductOfferStorages($sku);

        if ($this->config->isUnavailableProductsExcludedFromAddProductSearch()) {
            $productOfferStorageTransfers = $this->productOfferAvailabilityFilter->filterAvailable($productOfferStorageTransfers);
        }

        return $this->mapProductOfferStoragesToProductOfferTransfers($productOfferStorageTransfers);
    }

    /**
     * @return array<\Generated\Shared\Transfer\ProductOfferStorageTransfer>
     */
    protected function getProductOfferStorages(string $sku): array
    {
        $productOfferStorageCriteriaTransfer = (new ProductOfferStorageCriteriaTransfer())
            ->addProductConcreteSku($sku);

        return $this->productOfferStorageClient
            ->getProductOfferStoragesBySkus($productOfferStorageCriteriaTransfer)
            ->getProductOffers()
            ->getArrayCopy();
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductOfferStorageTransfer> $productOfferStorageTransfers
     *
     * @return array<\Generated\Shared\Transfer\ProductOfferTransfer>
     */
    protected function mapProductOfferStoragesToProductOfferTransfers(array $productOfferStorageTransfers): array
    {
        $merchantNamesByReference = $this->getMerchantNamesByReference($productOfferStorageTransfers);

        $productOfferTransfers = [];

        foreach ($productOfferStorageTransfers as $productOfferStorageTransfer) {
            $merchantReference = $productOfferStorageTransfer->getMerchantReference();

            if ($merchantReference === null) {
                continue;
            }

            $productOfferTransfers[] = (new ProductOfferTransfer())
                ->setConcreteSku($productOfferStorageTransfer->getProductConcreteSku())
                ->setMerchantName($merchantNamesByReference[$merchantReference] ?? $merchantReference)
                ->setMerchantReference($merchantReference)
                ->setProductOfferReference($productOfferStorageTransfer->getProductOfferReference());
        }

        return $productOfferTransfers;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductOfferStorageTransfer> $productOfferStorageTransfers
     *
     * @return array<string, string>
     */
    protected function getMerchantNamesByReference(array $productOfferStorageTransfers): array
    {
        $merchantStorageCriteriaTransfer = new MerchantStorageCriteriaTransfer();

        foreach ($productOfferStorageTransfers as $productOfferStorageTransfer) {
            if ($productOfferStorageTransfer->getMerchantReference() !== null) {
                $merchantStorageCriteriaTransfer->addMerchantReference($productOfferStorageTransfer->getMerchantReference());
            }
        }

        if ($merchantStorageCriteriaTransfer->getMerchantReferences() === []) {
            return [];
        }

        $merchantNamesByReference = [];

        foreach ($this->merchantStorageClient->get($merchantStorageCriteriaTransfer) as $merchantStorageTransfer) {
            $merchantReference = $merchantStorageTransfer->getMerchantReference();
            $merchantName = $merchantStorageTransfer->getName();

            if ($merchantReference !== null && $merchantName !== null) {
                $merchantNamesByReference[$merchantReference] = $merchantName;
            }
        }

        return $merchantNamesByReference;
    }
}
