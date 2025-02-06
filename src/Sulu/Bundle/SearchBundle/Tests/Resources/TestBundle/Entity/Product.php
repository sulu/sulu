<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity;

use Sulu\Component\Persistence\Model\TimestampableInterface;
use Sulu\Component\Persistence\Model\TimestampableTrait;
use Sulu\Component\Persistence\Model\UserBlameInterface;
use Sulu\Component\Persistence\Model\UserBlameTrait;

class Product implements TimestampableInterface, UserBlameInterface
{
    use TimestampableTrait;
    use UserBlameTrait;

    public $id;

    public $title;

    public $body;

    public $date;

    public $url;

    public $locale;

    public $image;
}
