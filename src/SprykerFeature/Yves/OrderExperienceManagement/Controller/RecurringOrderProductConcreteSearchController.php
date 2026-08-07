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
class RecurringOrderProductConcreteSearchController extends AbstractRecurringOrderController
{
    protected const string PARAM_SEARCH_STRING = 'searchString';

    protected const string PARAM_LIMIT = 'limit';

    protected const string RESULT_VIEW = '@OrderExperienceManagement/views/added-product-search-results/added-product-search-results.twig';

    public function indexAction(Request $request): View
    {
        $productConcretePageSearchTransfers = $this->getFactory()
            ->createAddedProductSearchReader()
            ->searchAvailableProductConcretes(
                (string)$request->query->get(static::PARAM_SEARCH_STRING, ''),
                $request->query->getInt(static::PARAM_LIMIT),
                $request->query->all(),
            );

        return $this->view(
            $productConcretePageSearchTransfers,
            [],
            static::RESULT_VIEW,
        );
    }
}
