<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Trait;

use Generated\Api\Backend\OrderCommentsBackendResource;
use Generated\Shared\Transfer\CommentTransfer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait OrderCommentResourceTrait
{
    protected const string URI_VARIABLE_ORDER_REFERENCE = 'orderReference';

    protected const string ERROR_ORDER_NOT_FOUND = 'No order with this orderReference.';

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function resolveOrderReference(): string
    {
        $orderReference = $this->findUriVariable(static::URI_VARIABLE_ORDER_REFERENCE);

        if (!is_string($orderReference) || $orderReference === '') {
            throw new HttpException(Response::HTTP_NOT_FOUND, static::ERROR_ORDER_NOT_FOUND);
        }

        return $orderReference;
    }

    protected function mapCommentTransferToResource(
        CommentTransfer $commentTransfer,
        string $orderReference
    ): OrderCommentsBackendResource {
        return (new OrderCommentsBackendResource())
            ->setOrderReference($orderReference)
            ->setMessage($commentTransfer->getMessage())
            ->setUsername($commentTransfer->getUsername())
            ->setCreatedAt($commentTransfer->getCreatedAt())
            ->setUpdatedAt($commentTransfer->getUpdatedAt());
    }
}
