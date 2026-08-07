<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Controller;

use Spryker\Yves\Kernel\View\View;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 */
class RecurringOrderProductPriceController extends AbstractRecurringOrderController
{
    public function indexAction(Request $request): View
    {
        $productOfferReference = $this->resolveProductOfferReference($request);

        if ($productOfferReference === null) {
            return $this->renderPrice(null);
        }

        $productOfferStorageTransfer = $this->getFactory()
            ->createProductOfferStorageResolver()
            ->resolveProductOfferStorage($productOfferReference);

        return $this->renderPrice($productOfferStorageTransfer?->getPrice()?->getPrice());
    }

    protected function renderPrice(?int $price): View
    {
        return $this->view(
            ['price' => $price],
            [],
            '@OrderExperienceManagement/views/added-item-price/added-item-price.twig',
        );
    }
}
