<?php

namespace DTL\Component\Content\Schema;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use DTL\Component\Content\Form\Extension\SuluTypeExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Validator\Validation;

class ContentSchemaTest extends \PHPUnit_Framework_TestCase
{
    public function testSchema()
    {
        $validator = Validation::createValidator();
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new SuluTypeExtension())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
        $builder = $factory->createBuilder('form');
        $builder->add('some_number', 'text', array(
            'required' => true
        ));
        $builder->add('animals', 'smart_content', array(
            'required' => true
        ));
        $builder->add('block', 'block', array(
            'children' => array(
                'title' => array('text'),
                'body' => array('text'),
                'website' => array('email'),
            ),
        ));

        $builder->add('articles', 'collection', array(
            'type' => 'block',
            'options' => array(
                'children' => array(
                    'title' => array('text'),
                    'body' => array('text'),
                    'website' => array('email'),
                ),
            ),
        ));

        $form = $builder->getForm();

        // bind some data to the form
        $contentData = array(
            'some_number' => '1234',
            'animals' => array(
                'title' => 'Smart content',
                'sort_method' => 'asc',
            ),
            'articles' => array(
            ),
        );
        $form->bind($contentData);

        if ($form->isValid()) {
            var_dump($form->isValid()); // true

            $view = $form->getNormData();
            var_dump($view); // dump the VIEW data
            die('valid');
        }

        var_dump($form->getErrorsAsString());
        die('Not valid!');
    }
}
