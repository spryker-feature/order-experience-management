<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * CSRF-protected form for the Review Required approval. Carries the schedule UUID and the prices the
 * buyer accepted on the page (each entry holding its group key + price), so approval is validated and
 * anchored to what was shown.
 */
class RecurringOrderApproveForm extends AbstractType
{
    public const string FORM_NAME = 'recurringOrderApproveForm';

    public const string FIELD_UUID = 'uuid';

    public const string FIELD_ACCEPTED_ITEMS = 'acceptedItems';

    public function getBlockPrefix(): string
    {
        return static::FORM_NAME;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addUuidField($builder);
        $this->addAcceptedItemsField($builder);
    }

    protected function addUuidField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_UUID, HiddenType::class, [
            'constraints' => [new NotBlank()],
        ]);

        return $this;
    }

    protected function addAcceptedItemsField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_ACCEPTED_ITEMS, CollectionType::class, [
            'required' => false,
            'label' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'entry_type' => RecurringOrderAcceptedItemForm::class,
            'entry_options' => [
                'label' => false,
            ],
        ]);

        return $this;
    }
}
