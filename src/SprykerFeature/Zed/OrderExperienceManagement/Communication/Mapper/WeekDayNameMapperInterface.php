<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Mapper;

interface WeekDayNameMapperInterface
{
    /**
     * @return array<int, string>
     */
    public function mapWeekDaysToNames(): array;
}
