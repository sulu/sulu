<?php
/*
  * This file is part of the Sulu CMS.
  *
  * (c) MASSIVE ART WebServices GmbH
  *
  * This source file is subject to the MIT license that is bundled
  * with this source code in the file LICENSE.
  */

namespace Sulu\Bundle\ContactBundle\Widgets;

use Sulu\Bundle\AdminBundle\Widgets\WidgetInterface;
use Sulu\Bundle\AdminBundle\Widgets\WidgetException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetParameterException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetEntityNotFoundException;
use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Contact;

/**
 * example widget for contact controller
 *
 * @package Sulu\Bundle\ContactBundle\Widgets
 */
class Contacts implements WidgetInterface
{

    protected $em;

    protected $accountEntityName = 'SuluContactBundle:Account';

    function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * return name of widget
     *
     * @return string
     */
    public function getName()
    {
        return 'contacts';
    }

    /**
     * returns template name of widget
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'SuluContactBundle:Widgets:contacts.html.twig';
    }

    /**
     * returns data to render template
     *
     * @param array $options
     * @throws WidgetException
     * @return array
     */
    public function getData($options)
    {
        if (!empty($options) &&
            array_key_exists('account', $options) &&
            !empty($options['account'])
        ) {
            $id = $options['account'];
            $account = $this->em->getRepository(
                $this->accountEntityName
            )->find($id);

            if (!$account) {
                throw new WidgetEntityNotFoundException(
                    'Entity ' . $this->accountEntityName . ' with id ' . $id . ' not found!',
                    $id
                );
            }
            return $this->parseContactsForAccountSidebar($account);
        } else {
            throw new WidgetParameterException(
                'Required parameter contact not found or empty!'
            );
        }
    }

    /**
     * Returns the data neede for the account list-sidebar
     *
     * @param Account $account
     * @return array
     */
    protected function parseContactsForAccountSidebar(Account $account)
    {

        $maxAccounts = 2;
        $i = 0;
        $contacts = $account->getContacts();

        if (count($contacts) > 0) {
            $data = [];
            while ($i < count($contacts) && $i < $maxAccounts) {
                $data[$i]['id'] = $contacts[$i]->getId();
                $data[$i]['fullName'] = $contacts[$i]->getFullName();
                $data[$i]['phone'] = $contacts[$i]->getMainPhone();
                $data[$i]['email'] = $contacts[$i]->getMainEmail();
                $i++;
            }
            return $data;
        } else {
            return null;
        }
    }
}
