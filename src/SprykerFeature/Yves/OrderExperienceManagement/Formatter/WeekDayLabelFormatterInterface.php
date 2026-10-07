<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Formatter;

interface WeekDayLabelFormatterInterface
{
    /**
     * @param iterable<\Generated\Shared\Transfer\RecurringScheduleTransfer> $recurringScheduleTransfers
     *
     * @return array<string, string>
     */
    public function formatWeekDayLabelsByScheduleUuid(iterable $recurringScheduleTransfers, string $localeName): array;
}
