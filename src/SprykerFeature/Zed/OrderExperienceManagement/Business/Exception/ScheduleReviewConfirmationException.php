<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Exception;

use RuntimeException;

/**
 * Thrown to roll back the applied schedule changes when the confirm state-machine event transitions nothing,
 * keeping the apply-and-confirm step atomic. Caught within the approver and mapped to a failure response.
 */
class ScheduleReviewConfirmationException extends RuntimeException
{
}
