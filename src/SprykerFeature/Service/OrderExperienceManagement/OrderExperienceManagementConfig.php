<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Service\OrderExperienceManagement;

use Spryker\Service\Kernel\AbstractBundleConfig;

/**
 * @method \SprykerFeature\Shared\OrderExperienceManagement\OrderExperienceManagementConfig getSharedConfig()
 */
class OrderExperienceManagementConfig extends AbstractBundleConfig
{
    /**
     * Specification:
     * - Returns the payment method keys that qualify as invoice-based payment.
     *
     * @api
     *
     * @return array<string>
     */
    public function getInvoicePaymentMethodKeys(): array
    {
        return $this->getSharedConfig()->getInvoicePaymentMethodKeys();
    }
}
