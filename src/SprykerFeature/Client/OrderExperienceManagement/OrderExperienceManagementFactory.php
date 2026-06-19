<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Client\OrderExperienceManagement;

use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\ZedRequest\ZedRequestClientInterface;
use SprykerFeature\Client\OrderExperienceManagement\Zed\OrderExperienceManagementStub;
use SprykerFeature\Client\OrderExperienceManagement\Zed\OrderExperienceManagementStubInterface;

class OrderExperienceManagementFactory extends AbstractFactory
{
    public function createOrderExperienceManagementStub(): OrderExperienceManagementStubInterface
    {
        return new OrderExperienceManagementStub($this->getZedRequestClient());
    }

    public function getZedRequestClient(): ZedRequestClientInterface
    {
        return $this->getProvidedDependency(OrderExperienceManagementDependencyProvider::CLIENT_ZED_REQUEST);
    }
}
