<?php

namespace Sulu\Bundle\TranslateBundle\Build;

use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

class TranslationBuilder extends SuluBuilder
{
    public function getName()
    {
        return 'translations';
    }

    public function getDependencies()
    {
        return array('database');
    }

    public function build()
    {
        foreach (array('en', 'de') as $locale) {
            $this->execCommand('Translations', 'sulu:translate:import', array('locale' => $locale));
            $this->execCommand('Translations', 'sulu:translate:export', array('locale' => $locale));
        }
    }
}
