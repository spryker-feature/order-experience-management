<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Service\OrderExperienceManagement;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Service\Kernel\AbstractService;

/**
 * @method \SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementServiceFactory getFactory()
 */
class OrderExperienceManagementService extends AbstractService implements OrderExperienceManagementServiceInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isEligibleForRecurringOrder(QuoteTransfer $quoteTransfer): bool
    {
        return $this->getFactory()
            ->createRecurringOrderQuoteEligibilityChecker()
            ->isEligibleForRecurringOrder($quoteTransfer);
    }
}
