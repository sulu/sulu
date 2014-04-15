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
            ' ' => '-',
            '+' => '-',
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            // because strtolower ignores Ä,Ö,Ü
            'Ä' => 'ae',
            'Ö' => 'oe',
            'Ü' => 'ue'
            // TODO should be filled
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
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string whole path
     */
    public function generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // get generated path from childClass
        $path = $this->generatePath($title, $parentPath);

        // cleanup path
        $path = $this->cleanup($path);

        // get unique path
        $path = $this->mapper->getUniquePath($path, $webspaceKey, $languageCode, $segmentKey);

        return $path;
    }

    /**
     * returns whole path for given ContentNode
     * @param string $title title of new node
     * @param string $uuid uuid for node to generate rl
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string whole path
     */
    public function generateForUuid($title, $uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        $parentPath = $this->mapper->getParentPath($uuid, $webspaceKey, $languageCode, $segmentKey);

        return $this->generate($title, $parentPath, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * internal generator
     * @param $title
     * @param $parentPath
     * @return string
     */
    protected abstract function generatePath($title, $parentPath = null);

    /**
     * returns a clean string
     * @param string $dirty dirty string to cleanup
     * @return string clean string
     */
    protected function cleanup($dirty)
    {
        $clean = strtolower($dirty);

        // TODO language
        $languageCode = 'de';
        $replacers = array_merge($this->replacers['default'], $this->replacers[$languageCode]);

        if (count($replacers) > 0) {
            foreach ($replacers as $key => $value) {
                $clean = str_replace($key, $value, $clean);
            }
        }

        // Inspired by ZOOLU
        // delete problematic characters
        $clean = str_replace('%2F', '/', urlencode(preg_replace('/([^A-za-z0-9\s-_\/])/', '', $clean)));

        // replace multiple minus with one
        $clean = preg_replace('/([-]+)/', '-', $clean);

        // delete minus at the beginning or end
        $clean = preg_replace('/^([-])/', '', $clean);
        $clean = preg_replace('/([-])$/', '', $clean);

        // remove double slashes
        $clean = str_replace('//', '/', $clean);

        return $clean;
    }

    /**
     * creates a new route for given path
     * @param NodeInterface $contentNode reference node
     * @param string $path path to generate
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function save(NodeInterface $contentNode, $path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->save($contentNode, $path, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * creates a new resourcelocator and creates the correct history
     * @param string $src old resource locator
     * @param string $dest new resource locator
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     */
    public function move($src, $dest, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->move($src, $dest, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns path for given contentNode
     * @param NodeInterface $contentNode reference node
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string path
     */
    public function loadByContent(NodeInterface $contentNode, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->loadByContent($contentNode, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns path for given contentNode
     * @param string $uuid uuid of contentNode
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string path
     */
    public function loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->loadByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * {@inheritdoc}
     */
    public function getHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey = null)
    {
        return $this->mapper->getHistoryByContentUuid($uuid, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * returns the uuid of referenced content node
     * @param string $resourceLocator requested RL
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return string uuid of content node
     */
    public function loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // delegate to mapper
        return $this->mapper->loadByResourceLocator($resourceLocator, $webspaceKey, $languageCode, $segmentKey);
    }

    /**
     * checks if path is valid
     * @param string $path path of route
     * @param string $webspaceKey key of portal
     * @param string $languageCode
     * @param string $segmentKey
     * @return bool
     */
    public function isValid($path, $webspaceKey, $languageCode, $segmentKey = null)
    {
        // check for valid signs and uniqueness
        return preg_match($this->pattern, $path) && $this->mapper->unique($path, $webspaceKey, $languageCode, $segmentKey);
    }
}
