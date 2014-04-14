<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Template\Dumper;


class PHPTemplateDumper extends TemplateDumper
{

    private $results;

    public function __construct(array $results)
    {
        $this->results = $results;
    }

    /**
     * Creates a new class with the data from the given collection
     * @param array $options
     * @return string
     */
    public function dump($options = array())
    {
        return $this->render(
            'StructureClass.php.twig',
            array(
                'cache_class' => $options['cache_class'],
                'base_class' => $options['base_class'],
                'content' => $this->results
            )
        );
    }
}
