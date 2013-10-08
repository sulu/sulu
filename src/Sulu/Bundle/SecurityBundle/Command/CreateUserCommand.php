<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Command;

use DateTime;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('sulu:user:create')
            ->setDescription('Create a user.')
            ->setDefinition(array(
                new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                new InputArgument('firstName', InputArgument::REQUIRED, 'The FirstName'),
                new InputArgument('lastName', InputArgument::REQUIRED, 'The LastName'),
                new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                new InputOption('god', null, InputOption::VALUE_NONE, 'Set the user as God')
            ));
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getArgument('username');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $emailText = $input->getArgument('email');
        $locale = $input->getArgument('locale');
        $password = $input->getArgument('password');
        $god = $input->getOption('god');

        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        $emailTypes = $doctrine->getRepository('SuluContactBundle:EmailType')->findAll();

        $email = new Email();
        $email->setEmail($emailText);
        $email->setEmailType($emailTypes[0]);

        $em->persist($email);
        $em->flush();

        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->addEmail($email);
        $contact->setCreated(new DateTime());
        $contact->setChanged(new DateTime());

        $em->persist($contact);
        $em->flush();

        $user = new User();
        $user->setContact($contact);
        $user->setUsername($username);
        $user->setSalt($this->generateSalt());
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $user->setLocale($locale);

        // TODO God Mode

        $em->persist($user);
        $em->flush();
    }

    /**
     * Generates a random salt for the password
     * @return string
     */
    private function generateSalt()
    {
        return $this->getContainer()->get('sulu_security.salt_generator')->getRandomSalt();
    }

    /**
     * Encodes the given password, for the given password, with he given salt and returns the result
     * @param $user
     * @param $password
     * @param $salt
     * @return mixed
     */
    private function encodePassword($user, $password, $salt)
    {
        $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($user);

        return $encoder->encodePassword($password, $salt);
    }

}
