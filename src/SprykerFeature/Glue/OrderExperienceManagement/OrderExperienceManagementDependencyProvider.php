<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement;

use Spryker\Glue\Kernel\Backend\AbstractBundleDependencyProvider;

class OrderExperienceManagementDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @return array<\SprykerFeature\Glue\OrderExperienceManagement\Dependency\Plugin\OrderResourceExpanderPluginInterface>
     */
    protected function getOrderResourceExpanderPlugins(): array
    {
        return [];
    }
}
