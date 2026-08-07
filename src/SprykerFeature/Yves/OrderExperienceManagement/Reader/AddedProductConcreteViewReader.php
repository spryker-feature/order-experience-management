<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\ProductViewTransfer;
use Spryker\Client\Locale\LocaleClientInterface;
use Spryker\Client\ProductStorage\ProductStorageClientInterface;

class AddedProductConcreteViewReader implements AddedProductConcreteViewReaderInterface
{
    protected const string MAPPING_TYPE_SKU = 'sku';

    protected const string KEY_ID_PRODUCT_CONCRETE = 'id_product_concrete';

    public function __construct(
        protected readonly ProductStorageClientInterface $productStorageClient,
        protected readonly LocaleClientInterface $localeClient,
    ) {
    }

    public function findProductConcreteView(string $sku): ?ProductViewTransfer
    {
        $localeName = $this->localeClient->getCurrentLocale();

        $productConcreteStorageData = $this->productStorageClient->findProductConcreteStorageDataByMapping(
            static::MAPPING_TYPE_SKU,
            $sku,
            $localeName,
        );

        $idProductConcrete = $productConcreteStorageData[static::KEY_ID_PRODUCT_CONCRETE] ?? null;

        if ($idProductConcrete === null) {
            return null;
        }

        return $this->getProductConcreteViewsBySku([(int)$idProductConcrete])[$sku] ?? null;
    }

    /**
     * @param array<int> $productConcreteIds
     *
     * @return array<string, \Generated\Shared\Transfer\ProductViewTransfer>
     */
    public function getProductConcreteViewsBySku(array $productConcreteIds): array
    {
        if ($productConcreteIds === []) {
            return [];
        }

        $productViewTransfers = $this->productStorageClient->getProductConcreteViewTransfers(
            $productConcreteIds,
            $this->localeClient->getCurrentLocale(),
        );

        $productViewTransfersBySku = [];

        foreach ($productViewTransfers as $productViewTransfer) {
            $sku = $productViewTransfer->getSku();

            if ($sku !== null) {
                $productViewTransfersBySku[$sku] = $productViewTransfer;
            }
        }

        return $productViewTransfersBySku;
    }
}
