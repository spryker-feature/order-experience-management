<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Notification\Reader;

use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use Spryker\Zed\Customer\Business\CustomerFacadeInterface;

class RecurringScheduleBuyerReader implements RecurringScheduleBuyerReaderInterface
{
    public function __construct(protected readonly CustomerFacadeInterface $customerFacade)
    {
    }

    public function findBuyerCustomer(RecurringScheduleTransfer $recurringScheduleTransfer): ?CustomerTransfer
    {
        $customerReference = $recurringScheduleTransfer->getCustomerReference();

        if ($customerReference === null) {
            return null;
        }

        $customerResponseTransfer = $this->customerFacade->findCustomerByReference($customerReference);

        if (!$customerResponseTransfer->getIsSuccess()) {
            return null;
        }

        return $customerResponseTransfer->getCustomerTransferOrFail();
    }
}
