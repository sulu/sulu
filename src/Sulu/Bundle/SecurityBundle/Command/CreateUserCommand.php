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
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\RoleInterface;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
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
        $this->setName('sulu:security:user:create')
            ->setDescription('Create a user.')
            ->setDefinition(
                array(
                    new InputArgument('username', InputArgument::REQUIRED, 'The username'),
                    new InputArgument('firstName', InputArgument::REQUIRED, 'The FirstName'),
                    new InputArgument('lastName', InputArgument::REQUIRED, 'The LastName'),
                    new InputArgument('email', InputArgument::REQUIRED, 'The email'),
                    new InputArgument('locale', InputArgument::REQUIRED, 'The locale'),
                    new InputArgument('password', InputArgument::REQUIRED, 'The password'),
                    new InputOption('god', null, InputOption::VALUE_NONE, 'Set the user as God')
                )
            );
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

        if (!$emailTypes) {
            throw new \RuntimeException(
                'Cannot find any SuluContactBundle:EmailType entities in database, maybe you ' .
                'should load the fixtures?'
            );
        }

        $email = new Email();
        $email->setEmail($emailText);
        $email->setEmailType($emailTypes[0]);

        $em->persist($email);
        $em->flush();

        $now = new DateTime();

        $contact = new Contact();
        $contact->setFirstName($firstName);
        $contact->setLastName($lastName);
        $contact->addEmail($email);
        $contact->setCreated($now);
        $contact->setChanged($now);
        $contact->setMainEmail($email->getEmail());

        $em->persist($contact);
        $em->flush();

        $user = new User();
        $user->setContact($contact);
        $user->setUsername($username);
        $user->setSalt($this->generateSalt());
        $user->setPassword($this->encodePassword($user, $password, $user->getSalt()));
        $user->setLocale($locale);
        $role = $this->getRole($doctrine, $now, $em);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale('[]'); // set all locales
        $em->persist($userRole);

        // TODO God Mode

        $em->persist($user);
        $em->flush();

        $output->writeln(sprintf('Created user <comment>%s</comment>', $username));
    }

    /**
     * @see Command
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getArgument('username')) {
            $username = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a username: ',
                function ($username) {
                    if (empty($username)) {
                        throw new \Exception('Username can not be empty');
                    }

                    return $username;
                }
            );
            $input->setArgument('username', $username);
        }

        if (!$input->getArgument('firstName')) {
            $result = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a FirstName: ',
                function ($username) {
                    if (empty($username)) {
                        throw new \Exception('FirstName can not be empty');
                    }

                    return $username;
                }
            );
            $input->setArgument('firstName', $result);
        }

        if (!$input->getArgument('lastName')) {
            $result = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose a LastName: ',
                function ($username) {
                    if (empty($username)) {
                        throw new \Exception('LastName can not be empty');
                    }

                    return $username;
                }
            );
            $input->setArgument('lastName', $result);
        }

        if (!$input->getArgument('email')) {
            $email = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose an email: ',
                function ($email) {
                    if (empty($email)) {
                        throw new \Exception('Email can not be empty');
                    }

                    return $email;
                }
            );
            $input->setArgument('email', $email);
        }

        if (!$input->getArgument('locale')) {
            $email = $this->getHelper('dialog')->askAndValidate(
                $output,
                'Please choose an locale: ',
                function ($email) {
                    if (empty($email)) {
                        throw new \Exception('Locale can not be empty');
                    }

                    return $email;
                }
            );
            $input->setArgument('locale', $email);
        }

        if (!$input->getArgument('password')) {
            $password = $this->getHelper('dialog')->askHiddenResponseAndValidate(
                $output,
                'Please choose a password: ',
                function ($password) {
                    if (empty($password)) {
                        throw new \Exception('Password can not be empty');
                    }

                    return $password;
                }
            );
            $input->setArgument('password', $password);
        }
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

    /**
     * @param Registry $doctrine
     * @param DateTime $now
     * @param ObjectManager $em
     * @return object|RoleInterface
     */
    protected function getRole(Registry $doctrine, DateTime $now, ObjectManager $em)
    {
        // find default role or create a new one
        $role = $doctrine->getRepository('SuluSecurityBundle:Role')->findOneBy(array(), array('id' => 'ASC'), 1);
        if (!$role) {
            $role = new Role();
            $role->setName('User');
            $role->setSystem('Sulu');
            $role->setCreated($now);
            $role->setChanged($now);
            $em->persist($role);

            return $role;
        }

        return $role;
    }
}
