<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Query;

class ListToTreeConverterTest extends \PHPUnit_Framework_TestCase
{
    private function createItem($path, $number)
    {
        return array('path' => $path, 'a' => $number, 'b' => 'b' . $number);
    }

    public function testConvert()
    {
        $i = 0;
        $data = array(
            $this->createItem('/', $i++),
            $this->createItem('/a', $i++),
            $this->createItem('/a/a', $i++),
            $this->createItem('/a/a/a', $i++),
            $this->createItem('/a/b/a', $i++),
            $this->createItem('/a/b/b', $i++),
            $this->createItem('/a/b/a', $i++),
            $this->createItem('/a/b/b', $i++),
            $this->createItem('/a/b/c', $i++),
            $this->createItem('/a/b/c', $i++),
            $this->createItem('/b', $i++),
            $this->createItem('/b/a', $i++),
            $this->createItem('/b/a/a', $i++),
            $this->createItem('/b/a/a/a', $i++),
            $this->createItem('/b/a/a/a/a', $i++),
            $this->createItem('/b/b', $i++),
            $this->createItem('/c', $i++),
            $this->createItem('/d', $i++),
            $this->createItem('/e', $i++),
            $this->createItem('/f', $i++),
            $this->createItem('/g', $i++),
        );

        $converter = new ListToTreeConverter();
        $result = $converter->convert($data);

    }
}
