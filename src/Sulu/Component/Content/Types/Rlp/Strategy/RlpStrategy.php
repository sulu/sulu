<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\Rlp\Strategy;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Types\Rlp\Mapper\RlpMapperInterface;

/**
 * base class for Resource Locator Path Strategy
 */
abstract class RlpStrategy implements RlpStrategyInterface
{

    /**
     * @var string name of strategy
     */
    protected $name;

    /**
     * @var RlpMapperInterface
     */
    protected $mapper;

    /**
     * replacers for cleanup
     * @var array
     */
    protected $replacers = array(
        'default' => array(
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue'
        ),
        'de' => array(
            '&' => 'und'
        ),
        'en' => array(
            '&' => 'and'
        )
    );

    /**
     * valid pattern for path
     * example: /products/machines
     *  + test whole input case insensitive
     *  + trailing slash
     *  + one or more sign (a-z, 0-9, -, _)
     *  + repeat
     * @var string
     */
    private $pattern = '/^(\/[a-z0-9-_]+)+$/i';

    /**
     * @param string $name name of RLP Strategy
     * @param RlpMapperInterface $mapper
     */
    public function __construct($name, RlpMapperInterface $mapper)
    {
        $this->name = $name;
        $this->mapper = $mapper;
    }

    /**
     * returns name of RLP Strategy (e.g. whole tree)
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * returns whole path for given ContentNode
     * @param string $title title of new node
     * @param string $parentPath parent path of new contentNode
     * @param string $portal key of portal
     * @return string whole path
     */
    public function generate($title, $parentPath, $portal)
    {
        // get generated path from childClass
        $path = $this->_generate($title, $parentPath);

        // cleanup path
        $path = $this->cleanup($path);

        // get unique path
        $path = $this->mapper->getUniquePath($path, $portal);

        return $path;
    }

    /**
     * internal generator
     * @param $title
     * @param $parentPath
     * @return string
     */
    protected abstract function _generate($title, $parentPath);

    /**
     * returns a clean string
     * @param string $dirty dirty string to cleanup
     * @return string clean string
     */
    protected function cleanup($dirty)
    {
        $clean = strtolower($dirty);

        // TODO language
        $language = 'de';
        $replacers = array_merge($this->replacers['default'], $this->replacers[$language]);

        if (count($replacers) > 0) {
            foreach ($replacers as $key => $value) {
                $clean = str_replace($key, $value, $clean);
            }
        }

        // Inspired by ZOOLU
        // delete problematic characters
        $clean = str_replace('%2F', '/', urlencode(preg_replace('/([^A-za-z0-9\s-_\/])/', '', $clean)));

        $clean = str_replace('+', '-', $clean);

        // replace multiple minus with one
        $clean = preg_replace('/([-]+)/', '-', $clean);

        // delete minus at the beginning or end
        $clean = preg_replace('/^([-])/', '', $clean);
        $clean = preg_replace('/([-])$/', '', $clean);

        return $clean;
    }

    /**
     * save route in storage with reference on given contentNode
     * @param NodeInterface $contentNode
     * @param string $path to generate
     * @param string $portal key of portal
     * @return int|string id or uuid of new route
     */
    public function save(NodeInterface $contentNode, $path, $portal)
    {
        // delegate to mapper
        return $this->mapper->save($contentNode, $path, $portal);
    }

    /**
     * checks if path is valid
     * @param string $path path of route
     * @param string $portal key of portal
     * @return bool
     */
    public function isValid($path, $portal)
    {
        // check for valid signs and uniqueness
        return preg_match($this->pattern, $path) && $this->mapper->unique($path, $portal);
    }
}
