<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Zed\OrderExperienceManagement\Business\Intake\Resolver;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer;
use Generated\Shared\Transfer\ProductMeasurementUnitTransfer;
use Generated\Shared\Transfer\StoreRelationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\ProductMeasurementUnit\Business\ProductMeasurementUnitFacadeInterface;
use SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Resolver\ProductMeasurementSalesUnitCodeResolver;

/**
 * Auto-generated group annotations
 *
 * @group SprykerFeatureTest
 * @group Zed
 * @group OrderExperienceManagement
 * @group Business
 * @group Intake
 * @group Resolver
 * @group ProductMeasurementSalesUnitCodeResolverTest
 * Add your own group annotations below this line
 */
class ProductMeasurementSalesUnitCodeResolverTest extends Unit
{
    protected const int ID_PRODUCT_CONCRETE = 11;

    protected const string CODE_ITEM = 'ITEM';

    protected const string CODE_BOX = 'BOX';

    protected const string STORE_NAME_DE = 'DE';

    protected const string STORE_NAME_AT = 'AT';

    public function testFindSalesUnitsByCodeReturnsTheSalesUnitNamingTheCode(): void
    {
        // Arrange
        $matchingSalesUnitTransfer = $this->createSalesUnit(static::CODE_ITEM);
        $resolver = $this->createResolver([
            $this->createSalesUnit(static::CODE_BOX),
            $matchingSalesUnitTransfer,
        ]);

        // Act
        $salesUnitTransfers = $resolver->findSalesUnitsByCode(
            static::ID_PRODUCT_CONCRETE,
            static::CODE_ITEM,
            static::STORE_NAME_DE,
        );

        // Assert
        $this->assertSame([$matchingSalesUnitTransfer], $salesUnitTransfers);
    }

    public function testFindSalesUnitsByCodeReturnsNothingWhenNoSalesUnitNamesTheCode(): void
    {
        // Arrange
        $resolver = $this->createResolver([$this->createSalesUnit(static::CODE_BOX)]);

        // Act
        $salesUnitTransfers = $resolver->findSalesUnitsByCode(
            static::ID_PRODUCT_CONCRETE,
            static::CODE_ITEM,
            static::STORE_NAME_DE,
        );

        // Assert
        $this->assertSame([], $salesUnitTransfers);
    }

    /**
     * The catalogue lookup returns every store's sales units, so a unit restricted to another store
     * reaches this class and must be filtered out here rather than trusted.
     */
    public function testFindSalesUnitsByCodeSkipsASalesUnitRestrictedToAnotherStore(): void
    {
        // Arrange
        $resolver = $this->createResolver([
            $this->createSalesUnit(static::CODE_ITEM, [static::STORE_NAME_AT]),
        ]);

        // Act
        $salesUnitTransfers = $resolver->findSalesUnitsByCode(
            static::ID_PRODUCT_CONCRETE,
            static::CODE_ITEM,
            static::STORE_NAME_DE,
        );

        // Assert
        $this->assertSame([], $salesUnitTransfers);
    }

    public function testFindSalesUnitsByCodeReturnsASalesUnitRelatedToTheOrderStore(): void
    {
        // Arrange
        $matchingSalesUnitTransfer = $this->createSalesUnit(
            static::CODE_ITEM,
            [static::STORE_NAME_AT, static::STORE_NAME_DE],
        );
        $resolver = $this->createResolver([$matchingSalesUnitTransfer]);

        // Act
        $salesUnitTransfers = $resolver->findSalesUnitsByCode(
            static::ID_PRODUCT_CONCRETE,
            static::CODE_ITEM,
            static::STORE_NAME_DE,
        );

        // Assert
        $this->assertSame([$matchingSalesUnitTransfer], $salesUnitTransfers);
    }

    /**
     * A sales unit related to no store at all is unrestricted, matching how the cart pre-check
     * behaves when it finds no store data.
     *
     * @dataProvider provideSalesUnitsWithoutStoreData
     *
     * @param array<string>|null $storeNames
     */
    public function testFindSalesUnitsByCodeTreatsASalesUnitWithoutStoreDataAsUnrestricted(?array $storeNames): void
    {
        // Arrange
        $matchingSalesUnitTransfer = $this->createSalesUnit(static::CODE_ITEM, $storeNames);
        $resolver = $this->createResolver([$matchingSalesUnitTransfer]);

        // Act
        $salesUnitTransfers = $resolver->findSalesUnitsByCode(
            static::ID_PRODUCT_CONCRETE,
            static::CODE_ITEM,
            static::STORE_NAME_DE,
        );

        // Assert
        $this->assertSame([$matchingSalesUnitTransfer], $salesUnitTransfers);
    }

    /**
     * @return iterable<string, array{array<string>|null}>
     */
    public function provideSalesUnitsWithoutStoreData(): iterable
    {
        yield 'no store relation at all' => [null];
        yield 'a store relation naming no store' => [[]];
    }

    public function testFindSalesUnitsByCodeReturnsEverySalesUnitSharingTheCode(): void
    {
        // Arrange
        $firstSalesUnitTransfer = $this->createSalesUnit(static::CODE_ITEM);
        $secondSalesUnitTransfer = $this->createSalesUnit(static::CODE_ITEM);
        $resolver = $this->createResolver([$firstSalesUnitTransfer, $secondSalesUnitTransfer]);

        // Act
        $salesUnitTransfers = $resolver->findSalesUnitsByCode(
            static::ID_PRODUCT_CONCRETE,
            static::CODE_ITEM,
            static::STORE_NAME_DE,
        );

        // Assert
        $this->assertSame([$firstSalesUnitTransfer, $secondSalesUnitTransfer], $salesUnitTransfers);
    }

    public function testFindSalesUnitsByCodeSkipsASalesUnitCarryingNoMeasurementUnit(): void
    {
        // Arrange
        $resolver = $this->createResolver([new ProductMeasurementSalesUnitTransfer()]);

        // Act
        $salesUnitTransfers = $resolver->findSalesUnitsByCode(
            static::ID_PRODUCT_CONCRETE,
            static::CODE_ITEM,
            static::STORE_NAME_DE,
        );

        // Assert
        $this->assertSame([], $salesUnitTransfers);
    }

    /**
     * @param array<string>|null $storeNames
     */
    protected function createSalesUnit(string $code, ?array $storeNames = null): ProductMeasurementSalesUnitTransfer
    {
        $productMeasurementSalesUnitTransfer = (new ProductMeasurementSalesUnitTransfer())
            ->setProductMeasurementUnit((new ProductMeasurementUnitTransfer())->setCode($code));

        if ($storeNames === null) {
            return $productMeasurementSalesUnitTransfer;
        }

        $storeRelationTransfer = new StoreRelationTransfer();

        foreach ($storeNames as $storeName) {
            $storeRelationTransfer->addStores((new StoreTransfer())->setName($storeName));
        }

        return $productMeasurementSalesUnitTransfer->setStoreRelation($storeRelationTransfer);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductMeasurementSalesUnitTransfer> $productMeasurementSalesUnitTransfers
     */
    protected function createResolver(array $productMeasurementSalesUnitTransfers): ProductMeasurementSalesUnitCodeResolver
    {
        $productMeasurementUnitFacadeMock = $this->createMock(ProductMeasurementUnitFacadeInterface::class);
        $productMeasurementUnitFacadeMock->method('getSalesUnitsByIdProduct')
            ->with(static::ID_PRODUCT_CONCRETE)
            ->willReturn($productMeasurementSalesUnitTransfers);

        return new ProductMeasurementSalesUnitCodeResolver($productMeasurementUnitFacadeMock);
    }
}
