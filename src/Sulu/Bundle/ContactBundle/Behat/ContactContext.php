<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContactBundle\Behat;

use Behat\Behat\Context\SnippetAcceptingContext;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\TestBundle\Behat\BaseContext;

/**
 * Behat context class for the ContactBundle.
 */
class ContactContext extends BaseContext implements SnippetAcceptingContext
{
    private static $emailTypeId = 100;

    /**
     * @Given the email type :type exists
     */
    public function theEmailTypeExists($type)
    {
        $emailType = new EmailType();
        $emailType->setId(self::$emailTypeId);
        self::$emailTypeId += 1;
        $emailType->setName($type);

        $this->getEntityManager()->persist($emailType);
        $this->getEntityManager()->flush();
    }

    /**
     * @Given the contact :firstName :lastName with :typeName email :email exists
     */
    public function theContactExists($firstName, $lastName, $typeName, $emailAddress)
    {
        $type = $this->getEntityManager()
            ->getRepository('SuluContactBundle:EmailType')
            ->findOneByName($typeName);

        if (!$type) {
            throw new \InvalidArgumentException(sprintf(
                'No email type "%s" found', $typeName
            ));
        }

        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);

        $email = new Email();
        $email->setEmail($emailAddress);
        $email->setEmailType($type);

        $contact->addEmail($email);
        $contact->setFormOfAddress(0);

        $this->getEntityManager()->persist($email);
        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->flush();
    }

    /**
     * @Then the contact :firstName :lastName should not exist
     */
    public function theContactShouldNotExist($firstName, $lastName)
    {
        $contact = $this->getEntityManager()
            ->getRepository('SuluContactBundle:Contact')->findOneBy([
                'firstName' => $firstName,
                'lastName' => $lastName,
            ]);

        if ($contact) {
            throw new \Exception(sprintf('Contact with firstname "%s" and lastname "%s" should NOT exist', $firstName, $lastName));
        }
    }

    /**
     * @Then the contact :firstName :lastName should exist
     */
    public function theContactShouldExist($firstName, $lastName)
    {
        $contact = $this->getEntityManager()
            ->getRepository('SuluContactBundle:Contact')->findOneBy([
                'firstName' => $firstName,
                'lastName' => $lastName,
            ]);

        if (!$contact) {
            throw new \Exception(sprintf('Contact with firstname "%s" and lastname "%s" should exist', $firstName, $lastName));
        }
    }

    /**
     * Return the contact manager.
     *
     * @return \Sulu\Bundle\ContactBundle\Contact\ContactManagerInterface
     */
    protected function getContactManager()
    {
        return $this->getService('sulu_contact.contact_manager');
    }

    /**
     * @Given I click the card containing ":text"
     */
    public function iClickOnTheEditIconInTheRowContaining($text)
    {
        $this->waitForText($text);
        $script = <<<'EOT'
            var f = function () {
                var cards = document.querySelectorAll(".card-item .head-name");

                for (var i = 0; i < cards.length; i++) {
                    if (cards[i].textContent == '%s') {
                        cards[i].click();
                    }
                };
            }

            f();
EOT;

        $script = sprintf($script, $text);
        $this->getSession()->executeScript($script);
    }
}
