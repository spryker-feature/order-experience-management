<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Reader;

use Generated\Shared\Transfer\ProductConcreteCriteriaFilterTransfer;
use Spryker\Client\Catalog\CatalogClientInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteAvailabilityFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\Filter\ProductConcreteRestrictionFilterInterface;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;

class AddedProductSearchReader implements AddedProductSearchReaderInterface
{
    /**
     * @uses \Spryker\Client\Catalog\Plugin\Elasticsearch\ResultFormatter\ProductConcreteCatalogSearchResultFormatterPlugin
     */
    protected const string RESULT_FORMATTER_KEY = 'ProductConcreteCatalogSearchResultFormatterPlugin';

    public function __construct(
        protected CatalogClientInterface $catalogClient,
        protected ProductConcreteAvailabilityFilterInterface $productConcreteAvailabilityFilter,
        protected OrderExperienceManagementConfig $config,
        protected ProductConcreteRestrictionFilterInterface $productConcreteRestrictionFilter,
    ) {
    }

    /**
     * @param array<string, mixed> $requestParams
     *
     * @return array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer>
     */
    public function searchAvailableProductConcretes(string $searchString, int $limit, array $requestParams): array
    {
        $productConcretePageSearchTransfers = $this->productConcreteRestrictionFilter->filterUnrestricted(
            $this->search($searchString, $limit, $requestParams),
        );

        if (!$this->config->isUnavailableProductsExcludedFromAddProductSearch()) {
            return $productConcretePageSearchTransfers;
        }

        return $this->productConcreteAvailabilityFilter->filterAvailable($productConcretePageSearchTransfers);
    }

    /**
     * @param array<string, mixed> $requestParams
     *
     * @return array<\Generated\Shared\Transfer\ProductConcretePageSearchTransfer>
     */
    protected function search(string $searchString, int $limit, array $requestParams): array
    {
        $productConcreteCriteriaFilterTransfer = (new ProductConcreteCriteriaFilterTransfer())
            ->setSearchString($searchString)
            ->setLimit($limit)
            ->setRequestParams($requestParams);

        $formattedProducts = $this->catalogClient->searchProductConcretesByFullText($productConcreteCriteriaFilterTransfer);

        return $formattedProducts[static::RESULT_FORMATTER_KEY] ?? [];
    }
}
