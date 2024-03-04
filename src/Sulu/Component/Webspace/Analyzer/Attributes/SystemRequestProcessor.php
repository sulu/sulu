<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Analyzer\Attributes;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\SecurityBundle\System\SystemStoreInterface;
use Sulu\Component\HttpKernel\SuluKernel;
use Sulu\Component\Webspace\PortalInformation;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @final
 *
 * This class is internal overriding or extending should not be required instead create an own RequestProcessor.
 */
class SystemRequestProcessor implements RequestProcessorInterface
{
    /**
     * @var SystemStoreInterface
     */
    private $systemStore;

    /**
     * @var string
     */
    private $context;

    public function __construct(
        SystemStoreInterface $systemStore,
        string $context
    ) {
        $this->systemStore = $systemStore;
        $this->context = $context;
    }

    public function process(Request $request, RequestAttributes $requestAttributes)
    {
        $attributes = [];
        if (SuluKernel::CONTEXT_ADMIN === $this->context) {
            $this->systemStore->setSystem(Admin::SULU_ADMIN_SECURITY_SYSTEM);
            $attributes['system'] = Admin::SULU_ADMIN_SECURITY_SYSTEM;

            return new RequestAttributes($attributes);
        }
        $portalInformation = $requestAttributes->getAttribute('portalInformation');

        if (!$portalInformation instanceof PortalInformation) {
            return new RequestAttributes($attributes);
        }

        $webspace = $portalInformation->getWebspace();

        $security = $webspace->getSecurity();
        if ($security) {
            $attributes['system'] = $security->getSystem();
            $this->systemStore->setSystem($attributes['system']);
        }

        return new RequestAttributes($attributes);
    }

    public function validate(RequestAttributes $attributes)
    {
        return true;
    }
}
