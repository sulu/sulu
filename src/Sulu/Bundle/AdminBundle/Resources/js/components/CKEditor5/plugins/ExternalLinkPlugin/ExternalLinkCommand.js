// @flow
import Command from '@ckeditor/ckeditor5-core/src/command';
import type {ExternalLinkEventInfo} from './types';

const LINK_HREF_ATTRIBUTE = 'linkHref';
const LINK_TARGET_ATTRIBUTE = 'linkTarget';

export default class ExternalLinkCommand extends Command {
    isEnabled: boolean;

    execute(eventInfo: ExternalLinkEventInfo) {
        this.editor.model.change((writer) => {
            const externalLinkAttributes = {
                [LINK_HREF_ATTRIBUTE]: eventInfo.url,
                [LINK_TARGET_ATTRIBUTE]: eventInfo.target,
            };

            const {selection} = eventInfo;
            if (selection && !selection.isCollapsed) {
                for (const range of selection.getRanges()) {
                    writer.setAttributes(externalLinkAttributes, range);
                }
            } else {
                const externalLink = writer.createText(eventInfo.url, externalLinkAttributes);
                this.editor.model.insertContent(externalLink);
            }
        });
    }

    refresh() {
        const selection = this.editor.model.document.selection;
        const firstPosition = selection.getFirstPosition();

        if (firstPosition && firstPosition.textNode && this.hasExternalLinkAttribute(firstPosition.textNode)) {
            this.isEnabled = false;
            return;
        }

        const range = selection.getFirstRange();

        for (const item of range.getItems()) {
            const textNode = item.textNode;

            if (!textNode || !this.hasExternalLinkAttribute(textNode)) {
                continue;
            }

            this.isEnabled = false;
            return;
        }

        this.isEnabled = true;
    }

    hasExternalLinkAttribute(textNode: Object) {
        return textNode.hasAttribute(LINK_HREF_ATTRIBUTE) || textNode.hasAttribute(LINK_TARGET_ATTRIBUTE);
    }
}
