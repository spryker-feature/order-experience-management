<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Communication\Console;

use Spryker\Zed\Kernel\Communication\Console\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Business\OrderExperienceManagementBusinessFactory getBusinessFactory()
 * @method \SprykerFeature\Zed\OrderExperienceManagement\Communication\OrderExperienceManagementCommunicationFactory getFactory()
 */
class RecurringOrderForecastRefreshConsole extends Console
{
    protected const string NAME = 'recurring-orders:forecast:refresh';

    protected const string DESCRIPTION = 'Recalculates the monthly recurring-volume forecast and stores it as a snapshot for the Back Office to read.';

    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription(static::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->info('Refreshing monthly recurring order forecast...');

        $this->getBusinessFactory()
            ->createRecurringScheduleForecastRefresher()
            ->refresh();

        $this->info('Monthly recurring order forecast refreshed.');

        return static::CODE_SUCCESS;
    }
}
