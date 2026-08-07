<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Spryker\Zed\ProductMeasurementUnit\Business\ProductMeasurementUnitFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper\AddedItemProductConcreteMapperInterface;

class AddedItemProductMeasurementUnitValidator implements AddedItemProductMeasurementUnitValidatorInterface
{
    protected const string GLOSSARY_KEY_MEASUREMENT_UNIT_NOT_SUPPORTED = 'recurring_orders.review.add_product.error.measurement_unit_not_supported';

    protected const string PARAMETER_SKU = '%sku%';

    public function __construct(
        protected readonly ProductMeasurementUnitFacadeInterface $productMeasurementUnitFacade,
        protected readonly AddedItemProductConcreteMapperInterface $addedItemProductConcreteMapper,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     */
    public function validate(array $itemTransfers, string $sku): ?ErrorTransfer
    {
        $productConcreteTransfers = $this->addedItemProductConcreteMapper->mapItemTransfersToProductConcreteTransfers($itemTransfers);

        if ($productConcreteTransfers === []) {
            return null;
        }

        $unrestrictedProductConcreteTransfers = $this->productMeasurementUnitFacade->filterProductsWithoutMeasurementUnit(
            array_values($productConcreteTransfers),
        );

        if (count($unrestrictedProductConcreteTransfers) === count($productConcreteTransfers)) {
            return null;
        }

        return (new ErrorTransfer())
            ->setMessage(static::GLOSSARY_KEY_MEASUREMENT_UNIT_NOT_SUPPORTED)
            ->setParameters([static::PARAMETER_SKU => $sku]);
    }
}
