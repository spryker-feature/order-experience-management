<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * CSRF-protected form shared by the simple recurring order actions (cancel, confirm, pause, skip).
 * Carries the schedule UUID used to redirect back to the detail page after the action.
 */
class RecurringOrderActionForm extends AbstractType
{
    public const string FORM_NAME = 'recurringOrderActionForm';

    public const string FIELD_UUID = 'uuid';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUuidField($builder);
    }

    protected function addUuidField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_UUID, HiddenType::class);

        return $this;
    }
}
