<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Checker;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ProductMeasurementUnitTransfer;
use Generated\Shared\Transfer\ProductViewTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\Checker\AddedProductMeasurementUnitChecker;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Checker
 * @group AddedProductMeasurementUnitCheckerTest
 */
class AddedProductMeasurementUnitCheckerTest extends Unit
{
    protected const string MEASUREMENT_UNIT_CODE = 'METR';

    public function testProductViewWithBaseUnitIsRestricted(): void
    {
        // Arrange
        $productViewTransfer = (new ProductViewTransfer())
            ->setBaseUnit((new ProductMeasurementUnitTransfer())->setCode(static::MEASUREMENT_UNIT_CODE));

        // Act
        $isRestricted = (new AddedProductMeasurementUnitChecker())->isRestricted($productViewTransfer);

        // Assert
        $this->assertTrue($isRestricted);
    }

    public function testProductViewWithoutBaseUnitIsNotRestricted(): void
    {
        // Act
        $isRestricted = (new AddedProductMeasurementUnitChecker())->isRestricted(new ProductViewTransfer());

        // Assert
        $this->assertFalse($isRestricted);
    }
}
