<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Service\OrderExperienceManagement;

use Spryker\Service\Kernel\AbstractServiceFactory;
use SprykerFeature\Service\OrderExperienceManagement\Checker\RecurringOrderQuoteEligibilityChecker;
use SprykerFeature\Service\OrderExperienceManagement\Checker\RecurringOrderQuoteEligibilityCheckerInterface;

/**
 * @method \SprykerFeature\Service\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class OrderExperienceManagementServiceFactory extends AbstractServiceFactory
{
    public function createRecurringOrderQuoteEligibilityChecker(): RecurringOrderQuoteEligibilityCheckerInterface
    {
        return new RecurringOrderQuoteEligibilityChecker($this->getConfig());
    }
}
