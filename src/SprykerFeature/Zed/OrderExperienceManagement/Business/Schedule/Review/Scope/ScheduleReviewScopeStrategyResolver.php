<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule\Review\Scope;

use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;

class ScheduleReviewScopeStrategyResolver implements ScheduleReviewScopeStrategyResolverInterface
{
    public function __construct(
        protected readonly ScheduleReviewScopeStrategyInterface $standingScheduleReviewScopeStrategy,
        protected readonly ScheduleReviewScopeStrategyInterface $occurrenceScheduleReviewScopeStrategy,
    ) {
    }

    public function resolve(?string $scope): ScheduleReviewScopeStrategyInterface
    {
        if ($scope === SharedOrderExperienceManagementConfig::SCOPE_OCCURRENCE) {
            return $this->occurrenceScheduleReviewScopeStrategy;
        }

        return $this->standingScheduleReviewScopeStrategy;
    }
}
