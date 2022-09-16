<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\TwoFactor;

use Sulu\Bundle\SecurityBundle\Entity\TwoFactor\EmailInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * @internal This is an internal class which should not be used by a project. Instead create an own service based on
 *           AuthCodeMailerInterface and configure that one in scheb/2fa bundle.
 *
 * @final
 */
class AuthCodeMailer implements AuthCodeMailerInterface
{
    private Address|string|null $senderAddress = null;

    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig,
        private TranslatorInterface $translator,
        private string $templateName,
        ?string $senderEmail,
        ?string $senderName,
    ) {
        if (null !== $senderEmail && null !== $senderName) {
            $this->senderAddress = new Address($senderEmail, $senderName);
        } elseif ($senderEmail) {
            $this->senderAddress = $senderEmail;
        }
    }

    public function sendAuthCode(EmailInterface $user): void
    {
        $authCode = $user->getEmailAuthCode();
        if (null === $authCode) {
            return;
        }

        $message = new TemplatedEmail();
        $message
            ->to($user->getEmailAuthRecipient())
            ->subject($this->translator->trans('sulu_admin.two_factor_email_subject', [], 'admin'))
            ->context([
                'auth_code' => $authCode,
                'user' => $user,
            ]);

        if ($this->twig->getLoader()->exists($this->templateName . '.html.twig')) {
            $message->htmlTemplate($this->templateName . '.html.twig');
        }

        if ($this->twig->getLoader()->exists($this->templateName . '.txt.twig')) {
            $message->textTemplate($this->templateName . '.txt.twig');
        }

        if ($this->senderAddress) {
            $message->from($this->senderAddress);
        }

        $this->mailer->send($message);
    }
}
