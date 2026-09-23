<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types=1);

namespace SprykerFeature\Zed\OrderExperienceManagement\Business\Intake\Validator;

use Generated\Shared\Transfer\OrderIntakeRequestTransfer;
use Generated\Shared\Transfer\OrderIntakeResponseTransfer;
use Generated\Shared\Transfer\OrderIntakeValidationIssueTransfer;
use Spryker\Zed\Locale\Business\LocaleFacadeInterface;

/**
 * Checks that the locale the payload names, if any, exists.
 */
class OrderIntakeLocaleValidator implements OrderIntakeLocaleValidatorInterface
{
    protected const string FIELD_LOCALE = 'locale';

    protected const string MESSAGE_LOCALE_NOT_FOUND = 'Locale "%localeName%" was not found.';

    public function __construct(
        protected readonly LocaleFacadeInterface $localeFacade,
    ) {
    }

    public function validateLocale(
        OrderIntakeRequestTransfer $orderIntakeRequestTransfer,
        OrderIntakeResponseTransfer $orderIntakeResponseTransfer,
    ): OrderIntakeResponseTransfer {
        $localeName = $orderIntakeRequestTransfer->getOrderLocaleName();

        if ($localeName === null || trim($localeName) === '') {
            return $orderIntakeResponseTransfer;
        }

        if ($this->localeFacade->hasLocale($localeName)) {
            return $orderIntakeResponseTransfer;
        }

        return $orderIntakeResponseTransfer->addValidationIssue(
            (new OrderIntakeValidationIssueTransfer())
                ->setField(static::FIELD_LOCALE)
                ->setMessage(static::MESSAGE_LOCALE_NOT_FOUND)
                ->setParameters(['%localeName%' => $localeName]),
        );
    }
}
