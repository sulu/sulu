<?php

namespace DTL\Component\Content\Form\Extension\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class BlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'min_occurs' => range(date('Y') - 120, date('Y')),
            'max_occurs' => range(date('Y') - 120, date('Y')),
            'children' => array(),
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['children'] as $key => $arguments) {
            $type = $arguments[0];

            $options = array();
            if (isset($arguments[1])) {
                $options = $arguments[1];
            }

            $builder->add($key, $type, array_replace(array(
                'property_path' => '['.$key.']',
            ), $options));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'block';
    }
}
