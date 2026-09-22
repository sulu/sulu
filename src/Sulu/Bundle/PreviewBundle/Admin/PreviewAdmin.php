<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class PreviewAdmin extends Admin
{
    /**
     * @see \Sulu\Bundle\AudienceTargetingBundle\Admin\AudienceTargetingAdmin::SECURITY_CONTEXT
     */
    private const TARGET_GROUPS_SECURITY_CONTEXT = 'sulu.settings.target-groups';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private int $previewDelay,
        private string $previewMode,
        private array $bundles,
        private ?SecurityCheckerInterface $securityChecker = null,
    ) {
        if (null === $this->securityChecker) {
            @trigger_deprecation('sulu/sulu', '2.6.27', 'Initializing "' . __CLASS__ . '" without securityChecker is deprecated, the target group select is offered without checking permissions without it.');
        }
    }

    public function getConfigKey(): ?string
    {
        return 'sulu_preview';
    }

    public function getConfig(): ?array
    {
        return [
            'endpoints' => [
                'start' => $this->urlGenerator->generate('sulu_preview.start'),
                'render' => $this->urlGenerator->generate('sulu_preview.render'),
                'update' => $this->urlGenerator->generate('sulu_preview.update'),
                'update-context' => $this->urlGenerator->generate('sulu_preview.update-context'),
                'stop' => $this->urlGenerator->generate('sulu_preview.stop'),
                'preview-link' => $this->urlGenerator->generate('sulu_preview.public_preview', ['token' => ':token'], RouterInterface::ABSOLUTE_URL),
            ],
            'debounceDelay' => $this->previewDelay,
            'mode' => $this->previewMode,
            'audienceTargeting' => $this->hasAudienceTargeting(),
        ];
    }

    /**
     * The target group select loads the target groups through the API, which requires the view permission
     * on the audience targeting context. Offering it to a user who does not have it makes that request
     * fail with a 403 and leaves the preview without its target groups.
     */
    private function hasAudienceTargeting(): bool
    {
        if (!\array_key_exists('SuluAudienceTargetingBundle', $this->bundles)) {
            return false;
        }

        if (null === $this->securityChecker) {
            return true;
        }

        return $this->securityChecker->hasPermission(self::TARGET_GROUPS_SECURITY_CONTEXT, PermissionTypes::VIEW);
    }
}
