<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement\Api\Backend\Provider;

use ApiPlatform\Metadata\GetCollection;
use Codeception\Test\Unit;
use Generated\Api\Backend\OrderCommentsBackendResource;
use Generated\Shared\Transfer\CommentTransfer;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Provider\OrderCommentsBackendProvider;
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
 * @group Provider
 * @group OrderCommentsBackendProviderTest
 * Add your own group annotations below this line
 */
class OrderCommentsBackendProviderTest extends Unit
{
    protected const string ORDER_REFERENCE = 'DE--1234';

    protected const int ID_SALES_ORDER = 42;

    public function testProvideCollectionMapsEveryCommentOfTheOrder(): void
    {
        // Arrange
        $orderCommentReaderMock = $this->createOrderCommentReaderMock(static::ID_SALES_ORDER, [
            (new CommentTransfer())->setMessage('First')->setUsername('Ada Lovelace')->setCreatedAt('2026-09-01 09:00:00'),
            (new CommentTransfer())->setMessage('Second')->setUsername('Grace Hopper')->setUpdatedAt('2026-09-02 10:00:00'),
        ]);

        // Act
        $resources = (new OrderCommentsBackendProvider($orderCommentReaderMock))->provide(
            new GetCollection(class: OrderCommentsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => new Request()],
        );

        // Assert
        $this->assertIsArray($resources);
        $this->assertCount(2, $resources);
        $this->assertSame(static::ORDER_REFERENCE, $resources[0]->getOrderReference());
        $this->assertSame('First', $resources[0]->getMessage());
        $this->assertSame('Ada Lovelace', $resources[0]->getUsername());
        $this->assertSame('2026-09-01 09:00:00', $resources[0]->getCreatedAt());
        $this->assertSame('Second', $resources[1]->getMessage());
        $this->assertSame('2026-09-02 10:00:00', $resources[1]->getUpdatedAt());
    }

    public function testProvideCollectionReturnsAnEmptyListForAnOrderWithoutComments(): void
    {
        // Act
        $resources = (new OrderCommentsBackendProvider($this->createOrderCommentReaderMock(static::ID_SALES_ORDER, [])))->provide(
            new GetCollection(class: OrderCommentsBackendResource::class),
            ['orderReference' => static::ORDER_REFERENCE],
            ['request' => new Request()],
        );

        // Assert
        $this->assertSame([], $resources);
    }

    /**
     * @dataProvider provideUnresolvableOrderReferences
     *
     * @param array<string, string> $uriVariables
     */
    public function testProvideCollectionRejectsAnUnresolvableOrderWith404(array $uriVariables): void
    {
        // Arrange
        $orderCommentReaderMock = $this->createOrderCommentReaderMock(null, []);
        $orderCommentReaderMock->expects($this->never())->method('getCommentsByIdSalesOrder');

        // Act
        try {
            (new OrderCommentsBackendProvider($orderCommentReaderMock))->provide(
                new GetCollection(class: OrderCommentsBackendResource::class),
                $uriVariables,
                ['request' => new Request()],
            );
            $this->fail('Expected an HttpException.');
        } catch (HttpException $httpException) {
            // Assert
            $this->assertSame(404, $httpException->getStatusCode());
        }
    }

    /**
     * @return array<string, array{array<string, string>}>
     */
    public function provideUnresolvableOrderReferences(): array
    {
        return [
            'unknown order reference' => [['orderReference' => 'DE--unknown']],
            'missing order reference' => [[]],
        ];
    }

    /**
     * @param list<\Generated\Shared\Transfer\CommentTransfer> $commentTransfers
     *
     * @return \SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Reader\OrderCommentReaderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createOrderCommentReaderMock(?int $idSalesOrder, array $commentTransfers): OrderCommentReaderInterface
    {
        $orderCommentReaderMock = $this->createMock(OrderCommentReaderInterface::class);
        $orderCommentReaderMock->method('findIdSalesOrderByOrderReference')->willReturn($idSalesOrder);
        $orderCommentReaderMock->method('getCommentsByIdSalesOrder')->willReturn($commentTransfers);

        return $orderCommentReaderMock;
    }
}
