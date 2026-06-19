<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Widget;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Yves\Kernel\Widget\AbstractWidget;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderSelectorWidget extends AbstractWidget
{
    protected const string PARAMETER_IS_VISIBLE = 'isVisible';

    protected const string PARAMETER_IS_CONFIRMED = 'isConfirmed';

    public function __construct(QuoteTransfer $quoteTransfer)
    {
        $this->addIsVisibleParameter($quoteTransfer);
        $this->addIsConfirmedParameter($quoteTransfer);
    }

    public static function getName(): string
    {
        return 'RecurringOrderSelectorWidget';
    }

    public static function getTemplate(): string
    {
        return '@OrderExperienceManagement/views/recurring-order-selector/recurring-order-selector.twig';
    }

    protected function addIsVisibleParameter(QuoteTransfer $quoteTransfer): void
    {
        $isVisible = $this->getFactory()->getOrderExperienceManagementService()->isEligibleForRecurringOrder($quoteTransfer);

        $this->addParameter(static::PARAMETER_IS_VISIBLE, $isVisible);
    }

    protected function addIsConfirmedParameter(QuoteTransfer $quoteTransfer): void
    {
        $this->addParameter(static::PARAMETER_IS_CONFIRMED, $quoteTransfer->getRecurringOrderSettings() !== null);
    }
}
