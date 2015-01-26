<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace Sulu\Bundle\WebsocketBundle\Connection;

use Ratchet\ConnectionInterface;

/**
 * Dummy connection for fallback
 */
class DummyConnection implements ConnectionInterface
{

    private $data;

    /**
     * {@inheritdoc}
     */
    function send($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function close()
    {
        // do nothing
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
