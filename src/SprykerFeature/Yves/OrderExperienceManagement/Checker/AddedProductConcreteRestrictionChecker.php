<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Checker;

use Generated\Shared\Transfer\ProductViewTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\AddedProductConcreteViewReaderInterface;

class AddedProductConcreteRestrictionChecker implements AddedProductConcreteRestrictionCheckerInterface
{
    /**
     * @param array<\SprykerFeature\Yves\OrderExperienceManagement\Dependency\Plugin\AddedProductConcreteRestrictionPluginInterface> $addedProductConcreteRestrictionPlugins
     */
    public function __construct(
        protected readonly AddedProductConcreteViewReaderInterface $addedProductConcreteViewReader,
        protected readonly OrderExperienceManagementConfig $orderExperienceManagementConfig,
        protected readonly AddedProductMeasurementUnitCheckerInterface $addedProductMeasurementUnitChecker,
        protected readonly AddedProductPackagingUnitCheckerInterface $addedProductPackagingUnitChecker,
        protected readonly array $addedProductConcreteRestrictionPlugins,
    ) {
    }

    public function isProductConcreteRestricted(string $sku): bool
    {
        if (!$this->isAnyRestrictionEnabled()) {
            return false;
        }

        $productViewTransfer = $this->addedProductConcreteViewReader->findProductConcreteView($sku);

        if ($productViewTransfer === null) {
            return false;
        }

        return $this->isProductViewRestricted($productViewTransfer);
    }

    public function isProductViewRestricted(ProductViewTransfer $productViewTransfer): bool
    {
        if ($this->isRestrictedByUnitCheckers($productViewTransfer)) {
            return true;
        }

        return $this->isRestrictedByPlugins($productViewTransfer);
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfersBySku
     *
     * @return array<string, bool>
     */
    public function getRestrictionsBySku(array $productViewTransfersBySku): array
    {
        if (!$this->isAnyRestrictionEnabled()) {
            return [];
        }

        $isRestrictedBySku = $this->getMeasurementUnitRestrictionsBySku($productViewTransfersBySku);

        $unrestrictedProductViewTransfersBySku = array_diff_key($productViewTransfersBySku, array_filter($isRestrictedBySku));
        $isPackagingUnitRestrictedByIdProductConcrete = $this->getPackagingUnitRestrictionsByIdProductConcrete(
            $unrestrictedProductViewTransfersBySku,
        );

        foreach ($unrestrictedProductViewTransfersBySku as $sku => $productViewTransfer) {
            $isRestrictedBySku[$sku] = $this->isRestrictedByPackagingUnit($productViewTransfer, $isPackagingUnitRestrictedByIdProductConcrete)
                || $this->isRestrictedByPlugins($productViewTransfer);
        }

        return $isRestrictedBySku;
    }

    public function isAnyRestrictionEnabled(): bool
    {
        return $this->addedProductConcreteRestrictionPlugins !== []
            || $this->orderExperienceManagementConfig->isMeasurementUnitProductAdditionRestricted()
            || $this->orderExperienceManagementConfig->isPackagingUnitProductAdditionRestricted();
    }

    protected function isRestrictedByUnitCheckers(ProductViewTransfer $productViewTransfer): bool
    {
        if (
            $this->orderExperienceManagementConfig->isMeasurementUnitProductAdditionRestricted()
            && $this->addedProductMeasurementUnitChecker->isRestricted($productViewTransfer)
        ) {
            return true;
        }

        return $this->orderExperienceManagementConfig->isPackagingUnitProductAdditionRestricted()
            && $this->addedProductPackagingUnitChecker->isRestricted($productViewTransfer);
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfersBySku
     *
     * @return array<string, bool>
     */
    protected function getMeasurementUnitRestrictionsBySku(array $productViewTransfersBySku): array
    {
        $isRestrictedBySku = [];

        foreach ($productViewTransfersBySku as $sku => $productViewTransfer) {
            $isRestrictedBySku[$sku] = $this->orderExperienceManagementConfig->isMeasurementUnitProductAdditionRestricted()
                && $this->addedProductMeasurementUnitChecker->isRestricted($productViewTransfer);
        }

        return $isRestrictedBySku;
    }

    /**
     * @param array<string, \Generated\Shared\Transfer\ProductViewTransfer> $productViewTransfersBySku
     *
     * @return array<int, bool>
     */
    protected function getPackagingUnitRestrictionsByIdProductConcrete(array $productViewTransfersBySku): array
    {
        if (!$this->orderExperienceManagementConfig->isPackagingUnitProductAdditionRestricted()) {
            return [];
        }

        return $this->addedProductPackagingUnitChecker->getRestrictionsByProductConcreteId($productViewTransfersBySku);
    }

    /**
     * @param array<int, bool> $isPackagingUnitRestrictedByIdProductConcrete
     */
    protected function isRestrictedByPackagingUnit(
        ProductViewTransfer $productViewTransfer,
        array $isPackagingUnitRestrictedByIdProductConcrete,
    ): bool {
        $idProductConcrete = $productViewTransfer->getIdProductConcrete();

        if ($idProductConcrete === null) {
            return false;
        }

        return ($isPackagingUnitRestrictedByIdProductConcrete[$idProductConcrete] ?? false) === true;
    }

    protected function isRestrictedByPlugins(ProductViewTransfer $productViewTransfer): bool
    {
        foreach ($this->addedProductConcreteRestrictionPlugins as $addedProductConcreteRestrictionPlugin) {
            if ($addedProductConcreteRestrictionPlugin->isRestricted($productViewTransfer)) {
                return true;
            }
        }

        return false;
    }
}
