<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Item\Addition\Mapper;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;

class AddedItemProductConcreteMapper implements AddedItemProductConcreteMapperInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array<\Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string, \Generated\Shared\Transfer\ProductConcreteTransfer>
     */
    public function mapItemTransfersToProductConcreteTransfers(array $itemTransfers): array
    {
        $productConcreteTransfers = [];

        foreach ($itemTransfers as $itemTransfer) {
            $productConcreteTransfer = $this->mapItemTransferToProductConcreteTransfer($itemTransfer);

            if ($productConcreteTransfer === null) {
                continue;
            }

            $productConcreteTransfers[$productConcreteTransfer->getSkuOrFail()] = $productConcreteTransfer;
        }

        return $productConcreteTransfers;
    }

    protected function mapItemTransferToProductConcreteTransfer(ItemTransfer $itemTransfer): ?ProductConcreteTransfer
    {
        $sku = $itemTransfer->getSku();
        $idProductConcrete = $itemTransfer->getId();
        $idProductAbstract = $itemTransfer->getIdProductAbstract();

        if ($sku === null || $idProductConcrete === null || $idProductAbstract === null) {
            return null;
        }

        return (new ProductConcreteTransfer())
            ->setSku($sku)
            ->setIdProductConcrete($idProductConcrete)
            ->setFkProductAbstract($idProductAbstract);
    }
}
