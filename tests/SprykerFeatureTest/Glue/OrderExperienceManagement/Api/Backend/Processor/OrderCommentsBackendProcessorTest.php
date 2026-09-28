<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend\Processor;

use ApiPlatform\Metadata\Post;
use Codeception\Test\Unit;
use Generated\Api\Backend\OrderCommentsBackendResource;
use Generated\Shared\Transfer\CommentTransfer;
use Generated\Shared\Transfer\UserTransfer;
use Spryker\Zed\Sales\Business\SalesFacadeInterface;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Processor\OrderCommentsBackendProcessor;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderCommentReaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Glue
 * @group OrderExperienceManagement
 * @group Api
 * @group Backend
 * @group Processor
 * @group OrderCommentsBackendProcessorTest
 * Add your own group annotations below this line
 */
class OrderCommentsBackendProcessorTest extends Unit
{
    protected const string ORDER_REFERENCE = 'DE--1234';

    protected const int ID_SALES_ORDER = 42;

    protected const string MESSAGE = 'Customer called, deliver after 5pm.';

    public function testProcessPostSavesTheCommentAgainstTheOrderAttributedToTheActingUser(): void
    {
        // Arrange
        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->expects($this->once())
            ->method('saveComment')
            ->with($this->callback(fn (CommentTransfer $commentTransfer): bool => $commentTransfer->getFkSalesOrder() === static::ID_SALES_ORDER
                && $commentTransfer->getMessage() === static::MESSAGE
                && $commentTransfer->getUsername() === 'Ada Lovelace'))
            ->willReturnCallback(fn (CommentTransfer $commentTransfer): CommentTransfer => $commentTransfer->setCreatedAt('2026-09-23 10:00:00'));

        // Act
        $result = $this->createProcessor($salesFacadeMock)->process(
            (new OrderCommentsBackendResource())->setMessage(static::MESSAGE),
            new Post(class: OrderCommentsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => $this->createRequestWithUser((new UserTransfer())->setFirstName('Ada')->setLastName('Lovelace'))],
        );

        // Assert
        $this->assertInstanceOf(OrderCommentsBackendResource::class, $result);
        $this->assertSame(static::ORDER_REFERENCE, $result->getOrderReference());
        $this->assertSame(static::MESSAGE, $result->getMessage());
        $this->assertSame('Ada Lovelace', $result->getUsername());
        $this->assertSame('2026-09-23 10:00:00', $result->getCreatedAt());
    }

    public function testProcessPostAttributesTheCommentToTheUsernameWhenTheUserHasNoName(): void
    {
        // Arrange
        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->expects($this->once())
            ->method('saveComment')
            ->with($this->callback(fn (CommentTransfer $commentTransfer): bool => $commentTransfer->getUsername() === 'admin@spryker.com'))
            ->willReturnArgument(0);

        // Act
        $this->createProcessor($salesFacadeMock)->process(
            (new OrderCommentsBackendResource())->setMessage(static::MESSAGE),
            new Post(class: OrderCommentsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => $this->createRequestWithUser((new UserTransfer())->setUsername('admin@spryker.com'))],
        );
    }

    public function testProcessPostRejectsAnUnknownOrderWith404(): void
    {
        // Arrange
        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->expects($this->never())->method('saveComment');

        // Act & Assert
        $this->assertStatusCode(404, fn () => $this->createProcessor($salesFacadeMock, null)->process(
            (new OrderCommentsBackendResource())->setMessage(static::MESSAGE),
            new Post(class: OrderCommentsBackendResource::class),
            ['orderReference' => 'DE--unknown'],
            ['request' => $this->createRequestWithUser((new UserTransfer())->setUsername('admin@spryker.com'))],
        ));
    }

    public function testProcessPostRejectsAMissingOrderReferenceWith404(): void
    {
        // Arrange
        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->expects($this->never())->method('saveComment');

        // Act & Assert
        $this->assertStatusCode(404, fn () => $this->createProcessor($salesFacadeMock)->process(
            (new OrderCommentsBackendResource())->setMessage(static::MESSAGE),
            new Post(class: OrderCommentsBackendResource::class),
            [],
            ['request' => $this->createRequestWithUser((new UserTransfer())->setUsername('admin@spryker.com'))],
        ));
    }

    /**
     * @dataProvider provideBlankMessages
     */
    public function testProcessPostRejectsABlankMessageWith422(?string $message): void
    {
        // Arrange
        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->expects($this->never())->method('saveComment');

        // Act & Assert
        $this->assertStatusCode(422, fn () => $this->createProcessor($salesFacadeMock)->process(
            (new OrderCommentsBackendResource())->setMessage($message),
            new Post(class: OrderCommentsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => $this->createRequestWithUser((new UserTransfer())->setUsername('admin@spryker.com'))],
        ));
    }

    /**
     * @return array<string, array{string|null}>
     */
    public function provideBlankMessages(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'whitespace only' => ['   '],
        ];
    }

    public function testProcessPostRejectsARequestWithoutAnActingUserWith401(): void
    {
        // Arrange
        $salesFacadeMock = $this->createMock(SalesFacadeInterface::class);
        $salesFacadeMock->expects($this->never())->method('saveComment');

        // Act & Assert
        $this->assertStatusCode(401, fn () => $this->createProcessor($salesFacadeMock)->process(
            (new OrderCommentsBackendResource())->setMessage(static::MESSAGE),
            new Post(class: OrderCommentsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => new Request()],
        ));
    }

    protected function assertStatusCode(int $expectedStatusCode, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected an HttpException.');
        } catch (HttpException $httpException) {
            $this->assertSame($expectedStatusCode, $httpException->getStatusCode());
        }
    }

    protected function createRequestWithUser(UserTransfer $userTransfer): Request
    {
        $request = new Request();
        $request->attributes->set('UserTransfer', $userTransfer);

        return $request;
    }

    protected function createProcessor(
        SalesFacadeInterface $salesFacade,
        ?int $idSalesOrder = self::ID_SALES_ORDER,
    ): OrderCommentsBackendProcessor {
        $orderCommentReaderMock = $this->createMock(OrderCommentReaderInterface::class);
        $orderCommentReaderMock->method('findIdSalesOrderByOrderReference')->willReturn($idSalesOrder);

        return new OrderCommentsBackendProcessor($salesFacade, $orderCommentReaderMock);
    }
}
