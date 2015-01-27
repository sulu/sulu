<?php

namespace DTL\Bundle\ContentBundle\Tests\Resources\Types;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class OverviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('url', 'text');
        $builder->add('article', 'text');
    }

    public function getName()
    {
        return 'overview';
    }
}
