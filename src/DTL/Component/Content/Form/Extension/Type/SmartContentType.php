<?php

namespace DTL\Component\Content\Form\Extension\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use DTL\Component\Content\Form\ContentTypeInterface;
use DTL\Component\Content\Form\ContentView;
use Doctrine\ODM\PHPCR\DocumentManager;

class SmartContentType implements ContentTypeInterface
{
    /**
     * @var ContentViewResolver
     */
    private $viewResolver;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @param ContentViewResolver $viewResolver
     */
    public function __construct(ContentViewResolver $viewResolver, DocumentManager $manager)
    {
        $this->viewResolver = $viewResolver;
    }
    
    public function setDefaultOptions(OptionsResolverInterface $optionsResolver)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', 'text');
        $builder->add('path', 'text');
        $builder->add('data_source', 'text'); // uuid
        $builder->add('include_sub_folders', 'checkbox');
        $builder->add('categories', 'collection', array(
            'type' => 'integer',
        ));
        $builder->add('tags', 'collection', array(
            'type' => 'integer',
        ));
        $builder->add('sort_by', 'collection', array(
            'type' => 'text',
        ));
        $builder->add('sort_method', 'choice', array(
            'choices' => array(
                'asc' => 'ASC',
                'desc' => 'DESC',
            ),
        ));
        $builder->add('limit_result', 'number');
    }

    /**
     * {@inheritdoc}
     */
    public function buildContentView(ContentView $view)
    {
        $documents = $this->documentManager->findBy(array());

        $view->setValue(
            $this->viewResolver->createIterator($documents)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
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
        return 'smart_content';
    }
}
