<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Provider;

use Spryker\ApiPlatform\State\Provider\AbstractBackendProvider;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderCommentReaderInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Trait\OrderCommentResourceTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Read side of `GET /orders/{orderReference}/comments` — the Back Office order comments of one order.
 */
class OrderCommentsBackendProvider extends AbstractBackendProvider
{
    use OrderCommentResourceTrait;

    public function __construct(protected readonly OrderCommentReaderInterface $orderCommentReader)
    {
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *
     * @return array<\Generated\Api\Backend\OrderCommentsBackendResource>
     */
    protected function provideCollection(): array
    {
        $orderReference = $this->resolveOrderReference();
        $idSalesOrder = $this->orderCommentReader->findIdSalesOrderByOrderReference($orderReference);

        if ($idSalesOrder === null) {
            throw new HttpException(Response::HTTP_NOT_FOUND, static::ERROR_ORDER_NOT_FOUND);
        }

        $resources = [];

        foreach ($this->orderCommentReader->getCommentsByIdSalesOrder($idSalesOrder) as $commentTransfer) {
            $resources[] = $this->mapCommentTransferToResource($commentTransfer, $orderReference);
        }

        return $resources;
    }
}
