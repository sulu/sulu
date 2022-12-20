<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Provider;

use Symfony\Contracts\Translation\TranslatorInterface;

final class FormOfAddressProvider implements FormOfAddressProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getValues(string $locale): array
    {
        return [
            [
                'name' => '1',
                'title' => $this->translator->trans('sulu_contact.female_form_of_address', [], 'admin', $locale),
            ],
            [
                'name' => '0',
                'title' => $this->translator->trans('sulu_contact.male_form_of_address', [], 'admin', $locale),
            ],
            [
                'name' => '2',
                'title' => $this->translator->trans('sulu_contact.neutral_form_of_address', [], 'admin', $locale),
            ],
        ];
    }
}
