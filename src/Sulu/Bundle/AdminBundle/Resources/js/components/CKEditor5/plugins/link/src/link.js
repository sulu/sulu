// @flow
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import LinkEditing from './linkediting';
import LinkUI from './linkui';

const internalAttributes = [];

export default function(
    pluginName: string,
    commandPrefix: string,
    tag: string,
    attributeName: string,
    internalAttribute: string,
    onLink: (
        setValue: (value: any) => void,
        currentValue: any
    ) => void,
    validateURL: boolean = false,
    linkIcon: any = undefined,
    linkKeystroke: ?string = undefined
) {
    internalAttributes.push(internalAttribute);

    /*
     * The link plugin.
     *
     * This is a "glue" plugin which loads the LinkEditing and the LinkUI features.
     */
    return class Link extends Plugin {
        static get requires() {
            return [
                LinkEditing(
                    commandPrefix,
                    internalAttribute,
                    internalAttributes,
                    tag,
                    attributeName,
                    validateURL
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
                    linkKeystroke
                ),
            ];
        }

        static get pluginName() {
            return pluginName;
        }
    };
}
