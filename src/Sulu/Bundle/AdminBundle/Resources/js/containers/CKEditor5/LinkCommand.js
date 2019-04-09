// @flow
import Command from '@ckeditor/ckeditor5-core/src/command';
import CKEditor5 from './CKEditor5';
import type {AttributeMap} from './types';

export default class LinkCommand extends Command {
    isEnabled: boolean = true;
    attributeMap: AttributeMap;
    titleProperty: string;

    constructor(editor: CKEditor5, attributeMap: AttributeMap, titleProperty: string) {
        super(editor);

        this.attributeMap = attributeMap;
        this.titleProperty = titleProperty;

        this.set('buttonEnabled', true);
    }

    execute(eventInfo: Object) {
        this.editor.model.change((writer) => {
            const linkAttributes = Object.keys(this.attributeMap).reduce((attributes, key) => {
                const eventInfoValue = eventInfo[this.attributeMap[key]];

                if (!eventInfoValue) {
                    return attributes;
                }

                attributes[key] = eventInfoValue;
                return attributes;
            }, {});

            linkAttributes.provider = eventInfo.provider;

            const {selection} = eventInfo;
            const firstPosition = selection ? selection.getFirstPosition() : undefined;
            const textNode = firstPosition ? firstPosition.textNode || firstPosition.nodeBefore : undefined;

            if (selection && !selection.isCollapsed) {
                for (const range of selection.getRanges()) {
                    writer.setAttributes(linkAttributes, range);
                }
            } else if (this.hasLinkAttribute(textNode)) {
                writer.setAttributes(linkAttributes, textNode);
            } else {
                const externalLink = writer.createText(eventInfo[this.titleProperty], linkAttributes);
                this.editor.model.insertContent(externalLink);
            }
        });
    }

    refresh() {
        const selection = this.editor.model.document.selection;
        const firstPosition = selection.getFirstPosition();

        if (firstPosition && firstPosition.textNode && this.hasLinkAttribute(firstPosition.textNode)) {
            this.buttonEnabled = false;
            return;
        }

        const range = selection.getFirstRange();

        for (const item of range.getItems()) {
            const textNode = item.textNode;

            if (!textNode || !this.hasLinkAttribute(textNode)) {
                continue;
            }

            this.buttonEnabled = false;
            return;
        }

        this.buttonEnabled = true;
    }

    hasLinkAttribute(node: ?Object) {
        if (!node || !node.hasAttribute) {
            return false;
        }

        return Object.keys(this.attributeMap).some((attribute) => node && node.hasAttribute(attribute));
    }
}
