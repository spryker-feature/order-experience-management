<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Processor;

use Generated\Api\Backend\OrderCommentsBackendResource;
use Generated\Shared\Transfer\CommentTransfer;
use Generated\Shared\Transfer\UserTransfer;
use Spryker\ApiPlatform\State\Processor\AbstractBackendProcessor;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderCommentReaderInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Trait\OrderCommentResourceTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Write side of `POST /orders/{orderReference}/comments`.
 */
class OrderCommentsBackendProcessor extends AbstractBackendProcessor
{
    use OrderCommentResourceTrait;

    /**
     * @uses \Spryker\Zed\Sales\Communication\Controller\CommentController::submitCommentForm()
     */
    protected const string AUTHOR_NAME_FORMAT = '%s %s';

    protected const string ERROR_NO_ACTING_USER = 'No authenticated Back Office user on the request.';

    protected const string ERROR_BLANK_MESSAGE = 'message must not be blank.';

    public function __construct(
        protected readonly SalesFacadeInterface $salesFacade,
        protected readonly OrderCommentReaderInterface $orderCommentReader,
    ) {
    }

    protected function processPost(mixed $data): OrderCommentsBackendResource
    {
        $orderReference = $this->resolveOrderReference();

        $commentTransfer = (new CommentTransfer())
            ->setFkSalesOrder($this->resolveIdSalesOrder($orderReference))
            ->setMessage($this->resolveMessage($data))
            ->setUsername($this->resolveAuthorName());

        return $this->mapCommentTransferToResource(
            $this->salesFacade->saveComment($commentTransfer),
            $orderReference,
        );
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function resolveIdSalesOrder(string $orderReference): int
    {
        $idSalesOrder = $this->orderCommentReader->findIdSalesOrderByOrderReference($orderReference);

        if ($idSalesOrder === null) {
            throw new HttpException(Response::HTTP_NOT_FOUND, static::ERROR_ORDER_NOT_FOUND);
        }

        return $idSalesOrder;
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function resolveAuthorName(): string
    {
        if (!$this->hasUser()) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, static::ERROR_NO_ACTING_USER);
        }

        return $this->formatAuthorName($this->getUser());
    }

    protected function formatAuthorName(UserTransfer $userTransfer): string
    {
        $authorName = trim(sprintf(
            static::AUTHOR_NAME_FORMAT,
            $userTransfer->getFirstName(),
            $userTransfer->getLastName(),
        ));

        return $authorName !== '' ? $authorName : $userTransfer->getUsernameOrFail();
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function resolveMessage(mixed $data): string
    {
        $message = $data instanceof OrderCommentsBackendResource ? $data->getMessage() : null;

        if ($message === null || trim($message) === '') {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, static::ERROR_BLANK_MESSAGE);
        }

        return $message;
    }
}
