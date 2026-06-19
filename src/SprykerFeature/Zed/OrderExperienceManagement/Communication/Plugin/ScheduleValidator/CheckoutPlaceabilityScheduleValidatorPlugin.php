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
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementFacadeInterface getFacade()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class CheckoutPlaceabilityScheduleValidatorPlugin extends AbstractPlugin implements ScheduleValidatorPluginInterface
{
    /**
     * {@inheritDoc}
     * - Reconstructs the quote from the schedule's stored `quoteData` JSON.
     * - Calls `CheckoutFacade::isPlaceableOrder()` to run all checkout pre-condition checks.
     * - Expands the provided validation result with per-item reviews and blocking errors when pre-conditions fail (e.g. discontinued SKU).
     * - Returns the result transfer unchanged when all pre-conditions pass or `quoteData` is absent.
     *
     * @api
     */
    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        return $this->getBusinessFactory()
            ->createCheckoutPlaceabilityValidator()
            ->validate($recurringScheduleTransfer, $recurringScheduleValidationResultTransfer);
    }
}
