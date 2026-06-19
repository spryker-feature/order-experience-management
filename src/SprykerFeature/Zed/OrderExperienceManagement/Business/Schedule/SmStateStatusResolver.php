<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule;

use SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig as SharedOrderExperienceManagementConfig;
use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class SmStateStatusResolver implements SmStateStatusResolverInterface
{
    public function resolveStatus(string $smStateName): ?string
    {
        return match ($smStateName) {
            SharedOrderExperienceManagementConfig::STATUS_ACTIVE => SharedOrderExperienceManagementConfig::STATUS_ACTIVE,
            SharedOrderExperienceManagementConfig::STATUS_PAUSED => SharedOrderExperienceManagementConfig::STATUS_PAUSED,
            SharedOrderExperienceManagementConfig::STATUS_CANCELLED => SharedOrderExperienceManagementConfig::STATUS_CANCELLED,
            SharedOrderExperienceManagementConfig::STATUS_FAILED => SharedOrderExperienceManagementConfig::STATUS_FAILED,
            SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED => SharedOrderExperienceManagementConfig::STATUS_REVIEW_REQUIRED,
            OrderExperienceManagementConfig::INITIAL_STATE => SharedOrderExperienceManagementConfig::STATUS_DRAFT,
            default => null,
        };
    }
}
