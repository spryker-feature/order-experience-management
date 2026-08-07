<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Form\DataProvider;

use DateTime;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Form\RecurringScheduleEditForm;

class RecurringScheduleEditFormDataProvider
{
    /**
     * @return array<string, mixed>
     */
    public function getData(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        $nextTriggerDate = $recurringScheduleTransfer->getNextTriggerDate();

        return [
            RecurringScheduleEditForm::FIELD_UUID => $recurringScheduleTransfer->getUuid(),
            RecurringScheduleEditForm::FIELD_NAME => $recurringScheduleTransfer->getName(),
            RecurringScheduleEditForm::FIELD_CADENCE_TYPE => $recurringScheduleTransfer->getCadenceType(),
            RecurringScheduleEditForm::FIELD_CADENCE_VALUE => $recurringScheduleTransfer->getCadenceValue(),
            RecurringScheduleEditForm::FIELD_NEXT_EXECUTION_DATE => $nextTriggerDate !== null ? new DateTime($nextTriggerDate) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(RecurringScheduleTransfer $recurringScheduleTransfer): array
    {
        return [
            RecurringScheduleEditForm::OPTION_RECURRING_SCHEDULE => $recurringScheduleTransfer,
        ];
    }
}
