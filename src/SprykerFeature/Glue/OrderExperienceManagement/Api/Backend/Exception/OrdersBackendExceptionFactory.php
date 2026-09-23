<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Exception;

use Spryker\ApiPlatform\Exception\GlueApiException;
use Symfony\Component\HttpFoundation\Response;

class OrdersBackendExceptionFactory implements OrdersBackendExceptionFactoryInterface
{
    protected const string ERROR_CODE_VALIDATION = '901';

    public function createValidationException(array $messages): GlueApiException
    {
        $messages = array_values($messages);

        $glueApiException = new GlueApiException(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            static::ERROR_CODE_VALIDATION,
            $messages[0] ?? 'Order intake was rejected.',
        );

        if (count($messages) > 1) {
            $glueApiException->setErrors($this->mapMessagesToErrors($messages));
        }

        return $glueApiException;
    }

    /**
     * @param array<int, string> $messages
     *
     * @return array<int, array{code: string, status: int, detail: string}>
     */
    protected function mapMessagesToErrors(array $messages): array
    {
        return array_map(fn (string $message): array => [
            'code' => static::ERROR_CODE_VALIDATION,
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'detail' => $message,
        ], $messages);
    }
}
