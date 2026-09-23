<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Exception;

use Spryker\ApiPlatform\Exception\GlueApiException;

interface OrdersBackendExceptionFactoryInterface
{
    /**
     * @param array<int, string> $messages
     */
    public function createValidationException(array $messages): GlueApiException;
}
