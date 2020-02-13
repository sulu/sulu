<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR;

use Symfony\Component\String\Slugger\SluggerInterface;

class PathCleanupSluggerDecorator implements PathCleanupInterface
{
    /** @var PathCleanupInterface */
    private $decorated;

    /** @var SluggerInterface */
    private $slugger;

    public function __construct(PathCleanupInterface $decorated, SluggerInterface $slugger)
    {
        $this->decorated = $decorated;
        $this->slugger = $slugger;
    }

    /**
     * @inheritDoc
     */
    public function cleanup($dirty, $languageCode = null)
    {
        $slug = $this->slugger->slug($dirty, '-', $languageCode);

        return $this->decorated->cleanup($slug->toString(), $languageCode);
    }

    /**
     * @inheritDoc
     */
    public function validate($path)
    {
        return $this->decorated->validate($path);
    }
}
