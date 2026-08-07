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
class RecurringOrderProductOfferSelectController extends AbstractRecurringOrderController
{
    /**
     * @uses \SprykerShop\Yves\MerchantProductOfferWidget\Form\MerchantProductOffersSelectForm::PRODUCT_OFFER_REFERENCE_CHOICES
     */
    protected const string OPTION_PRODUCT_OFFER_REFERENCE_CHOICES = 'product_offer_reference_choices';

    protected const string PARAM_SKU = 'sku';

    protected const string RESULT_VIEW = '@OrderExperienceManagement/views/merchant-product-offers-select-form/merchant-product-offers-select-form.twig';

    public function indexAction(Request $request): View
    {
        $productOfferTransfers = $this->getFactory()
            ->createAddedProductOfferReader()
            ->getAvailableProductOfferChoices((string)$request->query->get(static::PARAM_SKU, ''));

        if ($productOfferTransfers === []) {
            return $this->view([], [], static::RESULT_VIEW);
        }

        $form = $this->getFactory()
            ->createAddedProductOfferForm([static::OPTION_PRODUCT_OFFER_REFERENCE_CHOICES => $productOfferTransfers]);

        return $this->view(
            ['form' => $form->createView()],
            [],
            static::RESULT_VIEW,
        );
    }
}
