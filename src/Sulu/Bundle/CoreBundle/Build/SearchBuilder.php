<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Schema;

/**
 * Builder for initializing the search indexes.
 *
 * @internal no backward compatibility promise is given for this class
 */
class SearchBuilder extends SuluBuilder
{
    public function __construct(
        private readonly EngineInterface $engine,
        private readonly Schema $schema,
    ) {
    }

    public function getName()
    {
        return 'search';
    }

    public function getDependencies()
    {
        return [];
    }

    public function build()
    {
        $destroy = $this->input->getOption('destroy');

        foreach (\array_keys($this->schema->indexes) as $index) {
            if ($this->engine->existIndex($index)) {
                if (!$destroy) {
                    $this->output->writeln('Found existing search index ' . $index . ', skipping');

                    continue;
                }

                $this->execCommand('Dropping the search index', 'cmsig:seal:index-drop', [
                    'engine' => 'default',
                    'index' => $index,
                    '--force' => true,
                ]);
            }

            $this->execCommand('Creating the search index', 'cmsig:seal:index-create', ['--index' => $index]);
        }
    }
}
