<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity;

use Sulu\Component\Persistence\Model\TimestampableInterface;
use Sulu\Component\Persistence\Model\UserBlameInterface;

class Product implements TimestampableInterface, UserBlameInterface
{
    public $id;
    public $title;
    public $body;
    public $date;
    public $url;
    public $locale;
    public $image;
    public $changed;
    public $created;
    public $creator;
    public $changer;

    public function getChanged()
    {
        return $this->changed;
    }

    public function getChanger()
    {
        return $this->changer;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getCreator()
    {
        return $this->creator;
    }
}
