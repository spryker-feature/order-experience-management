<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Yves\OrderExperienceManagement\Reader;

use ArrayObject;
use Codeception\Test\Unit;
use Generated\Shared\Transfer\RecurringScheduleStatusCountTransfer;
use SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig;
use SprykerFeature\Yves\OrderExperienceManagement\Reader\RecurringOrderAttentionBannerReader;

/**
 * @group SprykerFeatureTest
 * @group Yves
 * @group OrderExperienceManagement
 * @group Reader
 * @group RecurringOrderAttentionBannerReaderTest
 */
class RecurringOrderAttentionBannerReaderTest extends Unit
{
    protected const int ID_CUSTOMER = 42;

    public function testBuildsConditionsScopedToTheCustomerAndTheAttentionStatuses(): void
    {
        // Arrange
        $config = new OrderExperienceManagementConfig();

        // Act
        $recurringScheduleConditionsTransfer = $this->createReader($config)->buildStatusCountConditions(static::ID_CUSTOMER);

        // Assert
        $this->assertSame([static::ID_CUSTOMER], $recurringScheduleConditionsTransfer->getCustomerIds());
        $this->assertSame($config->getAttentionBannerStatuses(), $recurringScheduleConditionsTransfer->getStatuses());
    }

    public function testMapsReturnedCountsOntoTheirStatuses(): void
    {
        // Arrange
        $config = new OrderExperienceManagementConfig();
        $attentionBannerStatuses = $config->getAttentionBannerStatuses();

        $recurringScheduleStatusCountTransfers = new ArrayObject([
            (new RecurringScheduleStatusCountTransfer())->setStatus($attentionBannerStatuses[0])->setCount(3),
            (new RecurringScheduleStatusCountTransfer())->setStatus($attentionBannerStatuses[1])->setCount(7),
        ]);

        // Act
        $attentionStatusCounts = $this->createReader($config)->getAttentionStatusCounts($recurringScheduleStatusCountTransfers);

        // Assert
        $this->assertSame(3, $attentionStatusCounts[$attentionBannerStatuses[0]]);
        $this->assertSame(7, $attentionStatusCounts[$attentionBannerStatuses[1]]);
    }

    public function testSeedsEveryAttentionStatusWithZeroSoTheBadgeSetStaysStable(): void
    {
        // Arrange
        $config = new OrderExperienceManagementConfig();
        $attentionBannerStatuses = $config->getAttentionBannerStatuses();

        $recurringScheduleStatusCountTransfers = new ArrayObject([
            (new RecurringScheduleStatusCountTransfer())->setStatus($attentionBannerStatuses[0])->setCount(1),
        ]);

        // Act
        $attentionStatusCounts = $this->createReader($config)->getAttentionStatusCounts($recurringScheduleStatusCountTransfers);

        // Assert
        $this->assertSame($attentionBannerStatuses, array_keys($attentionStatusCounts));
        $this->assertSame(1, $attentionStatusCounts[$attentionBannerStatuses[0]]);
        $this->assertSame(0, $attentionStatusCounts[$attentionBannerStatuses[1]]);
        $this->assertSame(0, $attentionStatusCounts[$attentionBannerStatuses[2]]);
    }

    public function testReturnsAllZeroCountsForAnEmptyStatusCountCollection(): void
    {
        // Arrange
        $config = new OrderExperienceManagementConfig();

        // Act
        $attentionStatusCounts = $this->createReader($config)->getAttentionStatusCounts(new ArrayObject());

        // Assert
        $this->assertSame(array_fill_keys($config->getAttentionBannerStatuses(), 0), $attentionStatusCounts);
        $this->assertSame(0, array_sum($attentionStatusCounts));
    }

    protected function createReader(OrderExperienceManagementConfig $config): RecurringOrderAttentionBannerReader
    {
        return new RecurringOrderAttentionBannerReader($config);
    }
}
