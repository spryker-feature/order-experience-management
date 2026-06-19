<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Plugin\Checkout;

use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\CheckoutExtension\Dependency\Plugin\CheckoutPostSaveInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrdersCheckoutPostSavePlugin extends AbstractPlugin implements CheckoutPostSaveInterface
{
    /**
     * {@inheritDoc}
     * - Does nothing when `CheckoutResponseTransfer.isSuccess` is false (order placement failed).
     * - Does nothing when `QuoteTransfer.recurringOrderSettings` is null.
     * - Does nothing when the quote originated from an RFQ.
     * - Does nothing when the quote's payment method is not invoice-based.
     * - Creates a recurring schedule in the database and registers it with the RecurringOrders StateMachine.
     *
     * @api
     *
     * @uses \Generated\Shared\Transfer\CheckoutResponseTransfer::IS_SUCCESS
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param \Generated\Shared\Transfer\CheckoutResponseTransfer $checkoutResponseTransfer
     *
     * @return void
     */
    public function executeHook(QuoteTransfer $quoteTransfer, CheckoutResponseTransfer $checkoutResponseTransfer): void
    {
        $this->getBusinessFactory()
            ->createScheduleWriter()
            ->saveRecurringScheduleFromCheckout($quoteTransfer, $checkoutResponseTransfer);
    }
}
