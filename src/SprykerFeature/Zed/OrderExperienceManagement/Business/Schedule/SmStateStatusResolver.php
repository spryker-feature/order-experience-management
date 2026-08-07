<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Schedule;

use SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig;

class SmStateStatusResolver implements SmStateStatusResolverInterface
{
    public function __construct(
        protected readonly OrderExperienceManagementConfig $config,
    ) {
    }

    public function resolveStatus(string $smStateName): ?string
    {
        return $this->config->getSmStateNameToStatusMap()[$smStateName] ?? null;
    }
}
