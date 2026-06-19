<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Widget;

use Spryker\Yves\Kernel\Widget\AbstractWidget;

class RecurringOrderMenuItemWidget extends AbstractWidget
{
    protected const string PARAMETER_IS_ACTIVE_PAGE = 'isActivePage';

    protected const string PAGE_KEY = 'recurring-orders';

    public function __construct(string $activePage)
    {
        $this->addParameter(
            static::PARAMETER_IS_ACTIVE_PAGE,
            $activePage === static::PAGE_KEY,
        );
    }

    public static function getName(): string
    {
        return 'RecurringOrderMenuItemWidget';
    }

    public static function getTemplate(): string
    {
        return '@OrderExperienceManagement/views/recurring-order-menu-item/recurring-order-menu-item.twig';
    }
}
