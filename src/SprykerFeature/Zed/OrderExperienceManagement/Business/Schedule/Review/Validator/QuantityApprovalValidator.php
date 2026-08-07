<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Validator;

use Generated\Shared\Transfer\ErrorTransfer;
use Generated\Shared\Transfer\RecurringScheduleEventRequestTransfer;
use Generated\Shared\Transfer\RecurringScheduleReviewResponseTransfer;

class QuantityApprovalValidator implements ScheduleApprovalValidatorInterface
{
    protected const string GLOSSARY_KEY_QUANTITY_INVALID = 'recurring_orders.review.quantity_invalid';

    protected const int MINIMUM_QUANTITY = 1;

    public function validate(
        RecurringScheduleEventRequestTransfer $recurringScheduleEventRequestTransfer,
        RecurringScheduleReviewResponseTransfer $recurringScheduleReviewResponseTransfer,
    ): ?ErrorTransfer {
        foreach ($recurringScheduleEventRequestTransfer->getAcceptedItems() as $recurringScheduleItemReviewTransfer) {
            $acceptedQuantity = $recurringScheduleItemReviewTransfer->getAcceptedQuantity();

            if ($acceptedQuantity !== null && $acceptedQuantity < static::MINIMUM_QUANTITY) {
                return $this->createError();
            }
        }

        foreach ($recurringScheduleEventRequestTransfer->getAddedItems() as $recurringScheduleItemAdditionTransfer) {
            $quantity = $recurringScheduleItemAdditionTransfer->getQuantity();

            if ($quantity === null || $quantity < static::MINIMUM_QUANTITY) {
                return $this->createError();
            }
        }

        return null;
    }

    protected function createError(): ErrorTransfer
    {
        return (new ErrorTransfer())->setMessage(static::GLOSSARY_KEY_QUANTITY_INVALID);
    }
}
