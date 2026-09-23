<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeatureTest\Glue\OrderExperienceManagement;

use Codeception\Actor;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderAddressMapper;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderExpenseMapper;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderIntakeRequestMapper;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderIntakeResponseMapper;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderItemMapper;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderPaymentMapper;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderResourceMapper;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderTotalsMapper;
use SprykerFeature\Glue\OrderExperienceManagement\Api\Backend\Mapper\OrderTransitionsResourceMapper;

/**
 * Inherited Methods
 *
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
 */
class OrderExperienceManagementGlueTester extends Actor
{
    use _generated\OrderExperienceManagementGlueTesterActions;

    public function createOrderResourceMapper(): OrderResourceMapper
    {
        $orderAddressMapper = new OrderAddressMapper();

        return new OrderResourceMapper(
            new OrderItemMapper($orderAddressMapper),
            new OrderTotalsMapper(),
            new OrderPaymentMapper(),
            new OrderExpenseMapper(),
            $orderAddressMapper,
        );
    }

    public function createOrderIntakeRequestMapper(): OrderIntakeRequestMapper
    {
        return new OrderIntakeRequestMapper();
    }

    public function createOrderIntakeResponseMapper(): OrderIntakeResponseMapper
    {
        return new OrderIntakeResponseMapper();
    }

    public function createOrderTransitionsResourceMapper(): OrderTransitionsResourceMapper
    {
        return new OrderTransitionsResourceMapper();
    }
}
