// @flow
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import ButtonView from '@ckeditor/ckeditor5-ui/src/button/buttonview';
import ListView from '@ckeditor/ckeditor5-ui/src/list/listview';
import ListItemView from '@ckeditor/ckeditor5-ui/src/list/listitemview';
import {createDropdown} from '@ckeditor/ckeditor5-ui/src/dropdown/utils';
import internalLinkTypeRegistry from './registries/InternalLinkTypeRegistry';
// $FlowFixMe
import linkIcon from '!!raw-loader!./link.svg'; // eslint-disable-line import/no-webpack-loader-syntax

export default class InternalLinkPlugin extends Plugin {
    init() {
        this.editor.ui.componentFactory.add('internalLink', (locale) => {
            const dropdownButton = createDropdown(locale);
            const list = new ListView(locale);

            dropdownButton.buttonView.set({
                icon: linkIcon,
            });

            internalLinkTypeRegistry.getKeys().forEach((key) => {
                const button = new ButtonView(locale);
                button.set({
                    label: internalLinkTypeRegistry.getTitle(key),
                    withText: true,
                });
                const listItem = new ListItemView(locale);
                listItem.children.add(button);
                button.delegate('execute').to(listItem);

                list.items.add(listItem);
            });

            list.items.delegate('execute').to(dropdownButton);

            dropdownButton.panelView.children.add(list);

            return dropdownButton;
        });
    }
}
