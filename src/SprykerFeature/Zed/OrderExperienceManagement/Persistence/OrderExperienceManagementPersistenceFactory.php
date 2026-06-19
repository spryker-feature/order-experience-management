<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Persistence;

use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleHistoryQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleItemQuery;
use Orm\Zed\OrderExperienceManagement\Persistence\SpyRecurringScheduleQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateHistoryQuery;
use Orm\Zed\StateMachine\Persistence\SpyStateMachineItemStateQuery;
use Spryker\Zed\Kernel\Persistence\AbstractPersistenceFactory;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper\RecurringScheduleHistoryMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper\RecurringScheduleItemMapper;
use SprykerFeature\Zed\OrderExperienceManagement\Persistence\Propel\Mapper\RecurringScheduleMapper;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementEntityManagerInterface getEntityManager()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Persistence\OrderExperienceManagementRepositoryInterface getRepository()
 */
class OrderExperienceManagementPersistenceFactory extends AbstractPersistenceFactory
{
    public function createRecurringScheduleQuery(): SpyRecurringScheduleQuery
    {
        return SpyRecurringScheduleQuery::create();
    }

    public function createRecurringScheduleItemQuery(): SpyRecurringScheduleItemQuery
    {
        return SpyRecurringScheduleItemQuery::create();
    }

    public function createRecurringScheduleMapper(): RecurringScheduleMapper
    {
        return new RecurringScheduleMapper();
    }

    public function createRecurringScheduleItemMapper(): RecurringScheduleItemMapper
    {
        return new RecurringScheduleItemMapper();
    }

    public function createRecurringScheduleHistoryMapper(): RecurringScheduleHistoryMapper
    {
        return new RecurringScheduleHistoryMapper();
    }

    public function createRecurringScheduleHistoryQuery(): SpyRecurringScheduleHistoryQuery
    {
        return SpyRecurringScheduleHistoryQuery::create();
    }

    /**
     * @module StateMachine
     */
    public function createStateMachineItemStateHistoryQuery(): SpyStateMachineItemStateHistoryQuery
    {
        return SpyStateMachineItemStateHistoryQuery::create();
    }

    /**
     * @module StateMachine
     */
    public function createStateMachineItemStateQuery(): SpyStateMachineItemStateQuery
    {
        return SpyStateMachineItemStateQuery::create();
    }
}
