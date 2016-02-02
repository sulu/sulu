<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TranslateBundle\Translate\Dumper;

use Symfony\Component\Translation\Dumper\FileDumper;
use Symfony\Component\Translation\MessageCatalogue;

class JsonFileDumper extends FileDumper
{
    /**
     * Transforms a domain of a message catalogue to its string representation.
     *
     * @param MessageCatalogue $messages
     * @param string           $domain
     *
     * @return string representation
     */
    protected function format(MessageCatalogue $messages, $domain)
    {
        $data = $messages->all($domain);

        return json_encode($data);
    }

    /**
     * Gets the file extension of the dumper.
     *
     * @return string file extension
     */
    protected function getExtension()
    {
        return 'json';
    }
}
