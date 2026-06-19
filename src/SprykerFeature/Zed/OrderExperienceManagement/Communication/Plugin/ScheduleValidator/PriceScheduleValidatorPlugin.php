<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator;

use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Generated\Shared\Transfer\RecurringScheduleValidationResultTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerFeature\Zed\OrderExperienceManagement\Dependency\Plugin\ScheduleValidatorPluginInterface;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class PriceScheduleValidatorPlugin extends AbstractPlugin implements ScheduleValidatorPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * - Compares each item's stored price against the current catalog price.
     * - Adds reviewReason=OrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_INCREASED when the current price exceeds the stored price.
     * - Adds reviewReason=OrderExperienceManagementConfig::REVIEW_REASON_GROUP_PRICE_UNAVAILABLE when no current price is available for an item.
     * - Sets isValid=false when at least one item review is added.
     *
     * @api
     */
    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        return $this->getBusinessFactory()
            ->createRecurringSchedulePriceValidator()
            ->validate($recurringScheduleTransfer, $recurringScheduleValidationResultTransfer);
    }
}
