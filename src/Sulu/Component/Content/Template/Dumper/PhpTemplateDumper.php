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

use Sulu\Component\Content\Structure;

/**
 * Class PhpTemplateDumper.
 */
class PhpTemplateDumper
{
    /**
     * @var
     */
    private $twig;

    /**
     * @param string $path path to twig templates
     * @param bool $debug
     */
    public function __construct($path, $debug)
    {
        if (strpos($path, '/') !== 0) {
            $path = __DIR__ . '/' . $path;
        }
        $this->twig = new \Twig_Environment(new \Twig_Loader_Filesystem($path), array('debug' => $debug));

        $this->twig->addFunction(
            new \Twig_SimpleFunction(
                'is_array', function ($value) {
                    return is_array($value);
                }
            )
        );

        if ($debug) {
            $this->twig->addExtension(new \Twig_Extension_Debug());
        }
    }

    /**
     * Creates a new class with the data from the given collection.
     *
     * @param array $results
     * @param array $options
     * @param string $type
     *
     * @return string
     */
    public function dump($results, $options = array(), $type = Structure::TYPE_PAGE)
    {
        return $this->twig->render(
            $type === Structure::TYPE_PAGE ? 'PageClass.php.twig' : 'SnippetClass.php.twig',
            array(
                'cache_class' => $options['cache_class'],
                'base_class' => $options['base_class'],
                'content' => $results,
            )
        );
    }
}
