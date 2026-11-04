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

namespace Sulu\Snippet\UserInterface\Controller\Website;

use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the preview of a snippet whose template declares no view.
 *
 * Snippets were never rendered on their own before the preview existed, so a template that
 * carries no `<view>` is the expected state rather than a mistake. Telling the editor what to
 * add beats failing on a missing controller.
 *
 * @internal This class should not be instantiated by a project. It may be changed or removed at any time.
 */
final class SnippetPreviewController
{
    public function indexAction(Request $request): Response
    {
        $templateKey = $request->attributes->get('templateKey');
        $notice = \sprintf(
            '<p style="font-family: sans-serif; padding: 16px;">'
            . 'The snippet template "%s" declares no &lt;view&gt;, so there is nothing to render here. '
            . 'Add a &lt;view&gt; and a &lt;controller&gt; to it to preview the snippet on its own.'
            . '</p>',
            \is_string($templateKey) ? \htmlspecialchars($templateKey, \ENT_NOQUOTES) : 'unknown'
        );

        if (true === $request->attributes->get('partial', false)) {
            return new Response($notice);
        }

        // Preview::removeContent() splits the document on the replacer, which the website
        // preview template writes on both sides of the content.
        return new Response(
            '<!DOCTYPE html><html><body>'
            . Preview::CONTENT_REPLACER . $notice . Preview::CONTENT_REPLACER
            . '</body></html>'
        );
    }
}
