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

use Doctrine\ORM\EntityNotFoundException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetInterface;
use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\ContactBundle\Entity\Address;
use Sulu\Bundle\AdminBundle\Widgets\WidgetException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetParameterException;
use Sulu\Bundle\AdminBundle\Widgets\WidgetEntityNotFoundException;

/**
 * example widget for contact controller
 *
 * @package Sulu\Bundle\ContactBundle\Widgets
 */
class AccountInfo implements WidgetInterface
{
    protected $em;

    protected $widgetName = 'AccountInfo';
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
        return 'account-info';
    }

    /**
     * returns template name of widget
     *
     * @return string
     */
    public function getTemplate()
    {
        return 'SuluContactBundle:Widgets:account.info.html.twig';
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
                    $this->widgetName,
                    $id
                );
            }
            return $this->parseAccountForListSidebar($account);
        } else {
            throw new WidgetParameterException(
                'Required parameter account not found or empty!',
                $this->widgetName,
                'account'
            );
        }
    }

    /**
     * Returns the data neede for the account list-sidebar
     *
     * @param Account $account
     * @return array
     */
    protected function parseAccountForListSidebar(Account $account)
    {
        $data = [];

        $data['id'] = $account->getId();
        $data['name'] = $account->getName();

        /* @var Address $accountAddress */
        $accountAddress = $account->getMainAddress();

        if (!!$accountAddress) {
            $data['address']['street'] = $accountAddress->getStreet();
            $data['address']['number'] = $accountAddress->getNumber();
            $data['address']['zip'] = $accountAddress->getZip();
            $data['address']['city'] = $accountAddress->getCity();
            $data['address']['country'] = $accountAddress->getCountry(
            )->getName();
        }

        $data['phone'] = $account->getMainPhone();
        $data['fax'] = $account->getMainFax();

        $data['email'] = $account->getMainEmail();
        $data['url'] = $account->getMainUrl();

        return $data;
    }
}
