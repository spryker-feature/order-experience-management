<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerFeature\Yves\OrderExperienceManagement\Form;

use Spryker\Yves\Kernel\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementFactory getFactory()
 * @method \SprykerFeature\Yves\OrderExperienceManagement\OrderExperienceManagementConfig getConfig()
 */
class RecurringOrderSearchFilterSubForm extends AbstractType
{
    public const string FIELD_SEARCH = 'search';

    public const string FIELD_STATUS = 'status';

    public const string FIELD_SCOPE = 'scope';

    public const string OPTION_SCOPE_CHOICES = 'scopeChoices';

    public const string OPTION_STATUS_CHOICES = 'statusChoices';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setRequired([
            static::OPTION_SCOPE_CHOICES,
            static::OPTION_STATUS_CHOICES,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSearchField($builder)
            ->addStatusField($builder, $options)
            ->addScopeField($builder, $options);
    }

    protected function addSearchField(FormBuilderInterface $builder): static
    {
        $builder->add(static::FIELD_SEARCH, TextType::class, [
            'label' => 'recurring_orders.list.form.search',
            'required' => false,
            'sanitize_xss' => true,
            'attr' => [
                'placeholder' => 'recurring_orders.list.form.search_placeholder',
            ],
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function addStatusField(FormBuilderInterface $builder, array $options): static
    {
        $builder->add(static::FIELD_STATUS, ChoiceType::class, [
            'choices' => $options[static::OPTION_STATUS_CHOICES],
            'required' => false,
            'placeholder' => 'recurring_orders.list.form.status.all',
            'label' => 'recurring_orders.list.form.status',
        ]);

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     *
     * Only adds scope field when the user has at least one company-level permission.
     */
    protected function addScopeField(FormBuilderInterface $builder, array $options): static
    {
        $scopeChoices = $options[static::OPTION_SCOPE_CHOICES];
        if (count($scopeChoices) <= 1) {
            return $this;
        }

        $builder->add(static::FIELD_SCOPE, ChoiceType::class, [
            'choices' => $scopeChoices,
            'required' => false,
            'placeholder' => false,
            'label' => 'recurring_orders.list.form.scope',
        ]);

        return $this;
    }
}
