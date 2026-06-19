<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Expander;

use Generated\Shared\Transfer\RecurringScheduleCollectionTransfer;
use Generated\Shared\Transfer\RecurringScheduleTransfer;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Cadence\CadenceResolverInterface;

class RecurringScheduleSkipPreviewExpander implements RecurringScheduleSkipPreviewExpanderInterface
{
    protected const string DATE_FORMAT = 'Y-m-d';

    public function __construct(protected CadenceResolverInterface $cadenceResolver)
    {
    }

    public function expandWithSkipPreview(
        RecurringScheduleCollectionTransfer $recurringScheduleCollectionTransfer,
    ): RecurringScheduleCollectionTransfer {
        foreach ($recurringScheduleCollectionTransfer->getRecurringSchedules() as $recurringScheduleTransfer) {
            $this->expandScheduleWithSkipPreview($recurringScheduleTransfer);
        }

        return $recurringScheduleCollectionTransfer;
    }

    protected function expandScheduleWithSkipPreview(RecurringScheduleTransfer $recurringScheduleTransfer): void
    {
        if ($recurringScheduleTransfer->getNextTriggerDate() === null || $recurringScheduleTransfer->getCadenceType() === null) {
            return;
        }

        if (!$this->cadenceResolver->isSupported($recurringScheduleTransfer->getCadenceTypeOrFail())) {
            return;
        }

        $recurringScheduleTransfer->setNextTriggerDateAfterSkip(
            $this->cadenceResolver->resolveNextTriggerDate($recurringScheduleTransfer)->format(static::DATE_FORMAT),
        );
    }
}
