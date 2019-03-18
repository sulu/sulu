// @flow
import Command from '@ckeditor/ckeditor5-core/src/command';
import CKEditor5 from '../../CKEditor5';
import {LINK_HREF_ATTRIBUTE, LINK_TARGET_ATTRIBUTE} from './constants';
import type {ExternalLinkEventInfo} from './types';

function hasExternalLinkAttribute(node: ?Object) {
    if (!node || !node.hasAttribute) {
        return false;
    }

    return node.hasAttribute(LINK_HREF_ATTRIBUTE) || node.hasAttribute(LINK_TARGET_ATTRIBUTE);
}

export default class ExternalLinkCommand extends Command {
    isEnabled: boolean = true;

    constructor(editor: CKEditor5) {
        super(editor);

        this.set('buttonEnabled', true);
    }

    execute(eventInfo: ExternalLinkEventInfo) {
        this.editor.model.change((writer) => {
            const externalLinkAttributes = {
                [LINK_HREF_ATTRIBUTE]: eventInfo.url,
                [LINK_TARGET_ATTRIBUTE]: eventInfo.target,
            };

            const {selection} = eventInfo;
            const firstPosition = selection ? selection.getFirstPosition() : undefined;

            if (selection && !selection.isCollapsed) {
                for (const range of selection.getRanges()) {
                    writer.setAttributes(externalLinkAttributes, range);
                }
            } else if (firstPosition && hasExternalLinkAttribute(firstPosition.textNode)) {
                writer.setAttributes(externalLinkAttributes, firstPosition.textNode);
            } else {
                const externalLink = writer.createText(eventInfo.url, externalLinkAttributes);
                this.editor.model.insertContent(externalLink);
            }
        });
    }

    refresh() {
        const selection = this.editor.model.document.selection;
        const firstPosition = selection.getFirstPosition();

        if (firstPosition && firstPosition.textNode && hasExternalLinkAttribute(firstPosition.textNode)) {
            this.buttonEnabled = false;
            return;
        }

        const range = selection.getFirstRange();

        for (const item of range.getItems()) {
            const textNode = item.textNode;

            if (!textNode || !hasExternalLinkAttribute(textNode)) {
                continue;
            }

            this.buttonEnabled = false;
            return;
        }

        this.buttonEnabled = true;
    }
}
