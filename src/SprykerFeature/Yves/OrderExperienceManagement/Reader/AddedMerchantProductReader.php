<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\MerchantStorageCriteriaTransfer;
use Generated\Shared\Transfer\ProductOfferTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\MerchantStorage\MerchantStorageClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class AddedMerchantProductReader implements AddedMerchantProductReaderInterface
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_ABSTRACT = 'id_product_abstract';

    protected const string KEY_ID_PRODUCT_CONCRETE = 'id_product_concrete';

    protected const string KEY_MERCHANT_REFERENCE = 'merchant_reference';

    public function __construct(
        protected ProductStorageClientInterface $productStorageClient,
        protected MerchantStorageClientInterface $merchantStorageClient,
        protected LocaleClientInterface $localeClient,
        protected ProductConcreteAvailabilityReaderInterface $productConcreteAvailabilityReader,
        protected OrderExperienceManagementConfig $config,
    ) {
    }

    public function findMerchantProductChoice(string $sku): ?ProductOfferTransfer
    {
        $localeName = $this->localeClient->getCurrentLocale();

        $productConcreteStorageData = $this->productStorageClient->findProductConcreteStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $sku,
            $localeName,
        );

        if ($productConcreteStorageData === null) {
            return null;
        }

        $merchantReference = $this->findOwningMerchantReference($productConcreteStorageData, $localeName);

        if ($merchantReference === null) {
            return null;
        }

        if ($this->config->isUnavailableProductsExcludedFromAddProductSearch() && !$this->isConcreteAvailable($productConcreteStorageData)) {
            return null;
        }

        $merchantName = $this->findMerchantName($merchantReference);

        if ($merchantName === null) {
            return null;
        }

        return (new ProductOfferTransfer())
            ->setConcreteSku($sku)
            ->setMerchantName($merchantName)
            ->setMerchantReference($merchantReference)
            ->setProductOfferReference('');
    }

    /**
     * @param array<string, mixed> $productConcreteStorageData
     */
    protected function findOwningMerchantReference(array $productConcreteStorageData, string $localeName): ?string
    {
        $idProductAbstract = $productConcreteStorageData[static::KEY_ID_PRODUCT_ABSTRACT] ?? null;

        if ($idProductAbstract === null) {
            return null;
        }

        $productAbstractStorageData = $this->productStorageClient->findProductAbstractStorageData((int)$idProductAbstract, $localeName);

        if ($productAbstractStorageData === null) {
            return null;
        }

        return $productAbstractStorageData[static::KEY_MERCHANT_REFERENCE] ?? null;
    }

    /**
     * @param array<string, mixed> $productConcreteStorageData
     */
    protected function isConcreteAvailable(array $productConcreteStorageData): bool
    {
        $idProductConcrete = $productConcreteStorageData[static::KEY_ID_PRODUCT_CONCRETE] ?? null;

        if ($idProductConcrete === null) {
            return true;
        }

        $isAvailableByIdProductConcrete = $this->productConcreteAvailabilityReader
            ->getAvailabilityByProductConcreteIds([(int)$idProductConcrete]);

        return ($isAvailableByIdProductConcrete[(int)$idProductConcrete] ?? true) === true;
    }

    protected function findMerchantName(string $merchantReference): ?string
    {
        $merchantStorageCriteriaTransfer = (new MerchantStorageCriteriaTransfer())
            ->addMerchantReference($merchantReference);

        foreach ($this->merchantStorageClient->get($merchantStorageCriteriaTransfer) as $merchantStorageTransfer) {
            if ($merchantStorageTransfer->getMerchantReference() === $merchantReference) {
                return $merchantStorageTransfer->getName();
            }
        }

        return null;
    }
}
