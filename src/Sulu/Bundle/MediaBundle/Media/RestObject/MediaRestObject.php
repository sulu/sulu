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

use Sulu\Bundle\MediaBundle\Entity\File;
use Sulu\Bundle\MediaBundle\Entity\FileVersion;
use Sulu\Bundle\MediaBundle\Entity\FileVersionContentLanguage;
use Sulu\Bundle\MediaBundle\Entity\FileVersionMeta;
use Sulu\Bundle\MediaBundle\Entity\FileVersionPublishLanguage;
use Sulu\Bundle\MediaBundle\Entity\Media;
use DateTime;
use Sulu\Bundle\TagBundle\Entity\Tag;

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
    protected $type;

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
                case 'id':
                    // set id
                    $this->id = $value;
                    break;
                case 'type':
                case 'collection':
                    // set collection
                    if ($value) {
                        $this->$key = $value['id'];
                    }
                    break;
                case 'changer':
                case 'creator':
                    if ($value) {
                        if (isset($value['contact']['firstName']) && isset($value['contact']['lastName'])) {
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
                                $versions[] = $fileVersion['version'];
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
                                    $this->contentLanguages = array();
                                    if ($fileVersion['contentLanguages']) {
                                        foreach ($fileVersion['contentLanguages'] as $contentLanguage) {
                                            if (!empty($contentLanguage['locale'])) {
                                                $this->contentLanguages[] = $contentLanguage['locale'];
                                            }
                                        }
                                    }
                                    $this->publishLanguages = array();
                                    if ($fileVersion['publishLanguages']) {
                                        foreach ($fileVersion['publishLanguages'] as $publishLanguage) {
                                            if (!empty($contentLanguage['locale'])) {
                                                $this->publishLanguages[] = $publishLanguage['locale'];
                                            }
                                        }
                                    }
                                    // TODO url
                                    // $this->url = $originPath . '/' . $fileVersion['id'];
                                    $this->thumbnails = array();
                                    // TODO thumbnails
                                    /*
                                    foreach ($fileFormats as $format) {
                                        $this->thumbnails[] = array(
                                            'format' => $format,
                                            'url' => $uploadPath . '/'.$format.'/' . $fileVersion['name']
                                        );
                                    }
                                    */

                                    if ($fileVersion['meta']) {
                                        $counter = 0;
                                        foreach ($fileVersion['meta'] as $meta) {
                                            $counter++;
                                            if ($counter == 1 || $meta['locale'] == $locale) {
                                                $this->title = $meta['title'];
                                                $this->description = $meta['description'];
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
     * @var Media $object
     * {@inheritdoc}
     */
    public function setDataByEntity($object, $locale, $version = null)
    {
        // set id
        $this->id = $object->getId();

        // set locale
        $this->locale = $locale;

        $versions = array();
        $contentLanguages = array();
        $publishLanguages = array();
        $tags = array();
        $thumbnails = array();

        /**
         * @var File $file
         */
        foreach ($object->getFiles() as $file) {
            if ($version === null) {
                $version = $file->getVersion();
            }
            // set version
            $this->version = $version;
            /**
             * @var FileVersion $fileVersion
             */
            foreach ($file->getFileVersions() as $fileVersion) {
                $versions[] = $fileVersion->getVersion();
                if ($version == $fileVersion->getVersion()) {
                    // set name
                    $this->name = $fileVersion->getName();

                    // set size
                    $this->size = $fileVersion->getSize();

                    /**
                     * @var FileVersionContentLanguage $contentLanguage
                     */
                    foreach ($fileVersion->getContentLanguages() as $contentLanguage) {
                        array_push($contentLanguages, $contentLanguage->getLocale());
                    }

                    /**
                     * @var FileVersionPublishLanguage $publishLanguage
                     */
                    foreach ($fileVersion->getPublishLanguages() as $publishLanguage) {
                        array_push($publishLanguages, $publishLanguage->getLocale());
                    }

                    /**
                     * @var Tag $tag
                     */
                    foreach ($fileVersion->getTags() as $tag) {
                        $tags[] = array(
                            'id' => $tag->getId(),
                            'name' =>$tag->getName()
                        );
                    }

                    /**
                     * @var FileVersionMeta $meta
                     */
                    $counter = 0;
                    foreach ($fileVersion->getMeta() as $meta) {
                        $counter++;
                        if ($counter == 1 || $meta->getLocale() == $locale) {
                            // set title
                            $this->title = $meta->getTitle();
                            // set description
                            $this->description = $meta->getDescription();
                        }
                    }

                    // TODO url
                    $fileVersion->getStorageOptions();
                    $this->url = null;

                    // TODO thumbnails
                    $thumbnails = array();
                }
            }
        }

        // set versions
        $this->versions = $versions;

        // set contentLanguages
        $this->contentLanguages = $contentLanguages;

        // set publishLanguages
        $this->publishLanguages = $publishLanguages;

        // set tags
        $this->tags = $tags;

        // set thumbnails
        $this->thumbnails = $thumbnails;

        // set collection
        if ($object->getCollection()) {
            $this->collection = $object->getCollection()->getId();
        }

        // set type
        if ($object->getType()) {
            $this->type = $object->getType()->getId();
        }

        // set changed time
        if ($object->getChanged() instanceof DateTime) {
            $this->changed = $object->getChanged();
        }

        // set created time
        if ($object->getCreated() instanceof DateTime) {
            $this->created = $object->getCreated();
        }

        // set changer
        if ($object->getChanger()) {
            $this->changer = ''; // TODO
        }

        // set creator
        if ($object->getCreator()) {
            $this->creator = ''; // TODO
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
                'type' => $this->type,
                'version' => $this->version,
                'versions' => $this->versions,
                'name' => $this->name,
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

        // Todo: move the sample picture to media-proxy if implemented
        if (!$this->thumbnails) {
            $data['thumbnails'] = array(
                '50x50' => array(
                    'url' => 'http://lorempixel.com/50/50/'
                ),
                '170x170' => array(
                    'url' => 'http://lorempixel.com/170/170/'
                )
            );
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

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
} 
