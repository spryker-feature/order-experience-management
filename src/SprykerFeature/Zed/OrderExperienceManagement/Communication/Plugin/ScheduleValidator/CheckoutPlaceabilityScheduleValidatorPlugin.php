<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\ScheduleValidator;

use Generated\Shared\Transfer\QuoteTransfer;
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
     * - Calls `CheckoutFacade::isPlaceableOrder()` with the provided placeable quote to run all checkout pre-condition checks.
     * - Expands the provided validation result with per-item reviews and blocking errors when pre-conditions fail (e.g. discontinued SKU).
     * - Returns the result transfer unchanged when all pre-conditions pass.
     *
     * @api
     */
    public function validate(
        RecurringScheduleTransfer $recurringScheduleTransfer,
        QuoteTransfer $quoteTransfer,
        RecurringScheduleValidationResultTransfer $recurringScheduleValidationResultTransfer,
    ): RecurringScheduleValidationResultTransfer {
        return $this->getBusinessFactory()
            ->createCheckoutPlaceabilityValidator()
            ->validate($recurringScheduleTransfer, $quoteTransfer, $recurringScheduleValidationResultTransfer);
    }
}
