/**
 * @license Copyright (c) 2003-2018, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md.
 */

/**
 * @module link/link
 */

import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import LinkEditing from './linkediting';
import LinkUI from './linkui';

const internalAttributes = [];

/**
 * @param {String} pluginName The plugin's name (Default: Link)
 * @param {String} commandPrefix The command's prefix
 * @param {String} tag The output tag name (Default: a)
 * @param {String} attributeName The attribute name (Default: href)
 * @param {String} internalAttribute The internal attribute key (Default `linkHref`)
 * @param {String} onLink The onLink callback
 * @param {Boolean} validateURL True if the value should be validated (Default: false)
 * @param {String} linkIcon The icons which will be shown in the toolbar
 * @param {String} linkKeystroke The keystroke which triggers the link command
 */
export default function(
    pluginName,
    commandPrefix,
    tag,
    attributeName,
    internalAttribute,
    onLink,
    validateURL = false,
    linkIcon = undefined,
    linkKeystroke = undefined,
) {
    internalAttributes.push(internalAttribute);

    /**
     * The link plugin.
     *
     * This is a "glue" plugin which loads the {@link module:link/linkediting~LinkEditing createLinkPlugin editing feature}
     * and {@link module:link/linkui~LinkUI createLinkPlugin UI feature}.
     *
     * @extends module:core/plugin~Plugin
     */
    return class Link extends Plugin {
        /**
         * @inheritDoc
         */
        static get requires() {
            return [
                LinkEditing(
                    commandPrefix,
                    internalAttribute,
                    internalAttributes,
                    tag,
                    attributeName,
                    validateURL,
                ),
                LinkUI(
                    pluginName,
                    commandPrefix,
                    internalAttribute,
                    tag,
                    attributeName,
                    onLink,
                    validateURL,
                    linkIcon,
                    linkKeystroke,
                ),
            ];
        }

        /**
         * @inheritDoc
         */
        static get pluginName() {
            return pluginName;
        }
    };
}
