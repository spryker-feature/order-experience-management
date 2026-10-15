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
 */
class RecurringOrderRecoverStuckConsole extends Console
{
    protected const string NAME = 'recurring-orders:recover-stuck';

    protected const string DESCRIPTION = 'Re-triggers the state machine event of recurring schedules that stayed in a transient state, and reports the ones that need manual attention.';

    protected function configure(): void
    {
        $this->setName(static::NAME)
            ->setDescription(static::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $recoveryResultTransfer = $this->getBusinessFactory()
            ->createStuckScheduleRecoverer()
            ->recoverStuckSchedules();

        $this->info(sprintf(
            'Recovered: %d, skipped: %d, failed: %d, needs manual attention: %d.',
            $recoveryResultTransfer->getRecoveredCountOrFail(),
            $recoveryResultTransfer->getSkippedCountOrFail(),
            $recoveryResultTransfer->getFailedCountOrFail(),
            $recoveryResultTransfer->getReportedCountOrFail(),
        ));

        if ($recoveryResultTransfer->getIsAborted() || $recoveryResultTransfer->getFailedCountOrFail() > 0) {
            return static::CODE_ERROR;
        }

        return static::CODE_SUCCESS;
    }
}
