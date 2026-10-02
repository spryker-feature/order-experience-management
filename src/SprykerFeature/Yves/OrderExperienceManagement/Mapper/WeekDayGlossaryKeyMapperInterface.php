<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Yves\OrderExperienceManagement\Mapper;

interface WeekDayGlossaryKeyMapperInterface
{
    /**
     * @return array<int, string>
     */
    public function mapWeekDaysToGlossaryKeys(): array;

    /**
     * @return array<string, string>
     */
    public function mapWeekDaysToChoices(): array;
}
