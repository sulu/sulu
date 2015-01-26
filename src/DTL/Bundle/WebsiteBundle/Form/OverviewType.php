<?php
<?php


namespace DTL\Bundle\WebsiteBundle\Form;
namespace DTL\Bundle\WebsiteBundle\Form;


class OverviewStructure
class OverviewStructure
{
{
    public function getLabel()
    public function getLabel()
    {
    {
        return array(
        return array(
            'de' => 'Komplex',
            'de' => 'Komplex',
            'en' => 'Complex',
            'en' => 'Complex',
        );
        );
    }
    }


    public function buildForm(FormBuilderInterface $builder)
    public function buildForm(FormBuilderInterface $builder)
    {
    {
        $builder->add('title', 'text_line', array(
        $builder->add('title', 'text_line', array(
            'requred' => true,
            'requred' => true,
        ));
        ));
        $builder->add('url'
        $builder->add('url'
    }
    }


    public function buildStructure(StructureBuilderInterface $builder)
    public function buildStructure(StructureBuilderInterface $builder)
    {
    {
        $builder->setLabels(array(
        $builder->setLabels(array(
            'de' => 'Komplex',
            'de' => 'Komplex',
            'en' => 'Complex',
            'en' => 'Complex',
        ));
        ));
        $builder->add('title', 'text_line', array(
        $builder->add('title', 'text_line', array(
            'labels' => array(
            'labels' => array(
                'de' => 'Titel',
                'de' => 'Titel',
                'en' => 'Title',
                'en' => 'Title',
            ),
            ),
        ))
        ))
    }
    }
}
}
