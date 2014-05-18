<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MediaBundle\Media\RestObject;

use Symfony\Component\Validator\Constraints\DateTime;

class MediaRestObject implements RestObject
{

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $name;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var int
     */
    protected $collection;

    /**
     * @var int
     */
    protected $version;

    /**
     * @var array
     */
    protected $versions = array();

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var array
     */
    protected $contentLanguages = array();

    /**
     * @var array
     */
    protected $publishLanguages = array();

    /**
     * @var array
     */
    protected $tags = array();

    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    protected $thumbnails = array();

    /**
     * @var array
     */
    protected $properties = array();

    /**
     * @var string
     */
    protected $changer;

    /**
     * @var string
     */
    protected $creator;

    /**
     * @var string
     */
    protected $changed;

    /**
     * @var
     */
    protected $created;

    /**
     * {@inheritdoc}
     */
    public function setDataByEntityArray($data, $locale, $version = null)
    {
        $this->locale = $locale;
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'collection':
                    if ($value) {
                        $this->collection = $value['id'];
                    }
                    break;
                case 'changer':
                case 'creator':
                    if ($value) {
                        if (isset($value['contact']['firstName'])) {
                            $this->$key = $value['contact']['firstName'] . ' ' . $value['contact']['lastName'];
                        }
                    }
                    break;
                case 'files':
                    if ($value) {
                        $file = $value[0];
                        // get actual file version when not set
                        if ($version === null) {
                            $version = $file['version'];
                        }
                        $versions = array();
                        if ($file['fileVersions']) {
                            foreach ($file['fileVersions'] as $fileVersion) {
                                $versions[] = array(
                                    'id' => $fileVersion['id'],
                                    'version' => $fileVersion['version'],
                                );
                                if ($fileVersion['version'] == $version) {
                                    $this->name = $fileVersion['name'];
                                    $this->size = $fileVersion['size'];
                                    $this->version = $fileVersion['version'];
                                    if ($fileVersion['changed'] instanceof DateTime) {
                                        $this->changed = $fileVersion['changed']->format('Y-m-d H:i:s');
                                    }
                                    if ($fileVersion['created'] instanceof DateTime) {
                                        $this->created = $fileVersion['created']->format('Y-m-d H:i:s');
                                    }
                                    $this->contentLanguages = $fileVersion['contentLanguages'];
                                    $this->publishLanguages = $fileVersion['publishLanguages'];
                                    // $this->url = $originPath . '/' . $fileVersion['id']; TODO
                                    $this->thumbnails = array();
                                    /* TODO
                                    foreach ($fileFormats as $format) {
                                        $this->thumbnails[] = array(
                                            'format' => $format,
                                            'url' => $uploadPath . '/'.$format.'/' . $fileVersion['name']
                                        );
                                    }
                                    */

                                    if ($fileVersion['metas']) {
                                        $metaSet = false;
                                        foreach ($fileVersion['metas'] as $meta) {
                                            if ($meta['locale'] == $locale) {
                                                $metaSet = true;
                                                $this->title = $meta['title'];
                                                $this->description = $meta['description'];
                                            }
                                        }
                                        if (!$metaSet) {
                                            if (isset($fileVersion['metas'][0])) {
                                                $this->title = $fileVersion['metas'][0]['title'];
                                                $this->description = $fileVersion['metas'][0]['description'];
                                            }
                                        }
                                    }

                                    break;
                                }
                            }
                        }
                        $this->versions = $versions;
                    }
                    break;
                default:
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray($fields = array())
    {
        if (empty($fields)) {
            // get all fields
            $data = array(
                'id' => $this->id,
                'locale' => $this->locale,
                'collection' => $this->collection,
                'versions' => $this->versions,
                'title' => $this->title,
                'description' => $this->description,
                'size' => $this->size,
                'contentLanguages' => $this->contentLanguages,
                'publishLanguages' => $this->publishLanguages,
                'tags' => $this->tags,
                'url' => $this->url,
                'thumbnails' => $this->thumbnails,
                'properties' => $this->properties,
                'changer' => $this->changer,
                'creator' => $this->creator,
                'changed' => $this->changed,
                'created' => $this->created
            );
        } else {
            // only get specific fields
            $data = array();
            foreach ($fields as $field) {
                if (isset($this->$field)) {
                    $data[$field] = $this->$field;
                }
            }
        }
        return $data;
    }

    /**
     * @param string $changed
     * @return $this
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
        return $this;
    }

    /**
     * @return string
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * @param string $changer
     * @return $this
     */
    public function setChanger($changer)
    {
        $this->changer = $changer;
        return $this;
    }

    /**
     * @return string
     */
    public function getChanger()
    {
        return $this->changer;
    }

    /**
     * @param int $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return int
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param array $contentLanguages
     * @return $this
     */
    public function setContentLanguages($contentLanguages)
    {
        $this->contentLanguages = $contentLanguages;
        return $this;
    }

    /**
     * @return array
     */
    public function getContentLanguages()
    {
        return $this->contentLanguages;
    }

    /**
     * @param mixed $created
     * @return $this
     */
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param string $creator
     * @return $this
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param array $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $publishLanguages
     * @return $this
     */
    public function setPublishLanguages($publishLanguages)
    {
        $this->publishLanguages = $publishLanguages;
        return $this;
    }

    /**
     * @return array
     */
    public function getPublishLanguages()
    {
        return $this->publishLanguages;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param array $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param array $thumbnails
     * @return $this
     */
    public function setThumbnails($thumbnails)
    {
        $this->thumbnails = $thumbnails;
        return $this;
    }

    /**
     * @return array
     */
    public function getThumbnails()
    {
        return $this->thumbnails;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param int $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param array $versions
     * @return $this
     */
    public function setVersions($versions)
    {
        $this->versions = $versions;
        return $this;
    }

    /**
     * @return array
     */
    public function getVersions()
    {
        return $this->versions;
    }

    /**
     * @param int $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getName()
    {
        return $this->name;
    }

} 