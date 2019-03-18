// @flow
import Command from '@ckeditor/ckeditor5-core/src/command';
import {LINK_HREF_ATTRIBUTE, LINK_TARGET_ATTRIBUTE, hasExternalLinkAttribute} from './utils';
import type {ExternalLinkEventInfo} from './types';

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

        if (firstPosition && firstPosition.textNode && hasExternalLinkAttribute(firstPosition.textNode)) {
            this.isEnabled = false;
            return;
        }

        const range = selection.getFirstRange();

        for (const item of range.getItems()) {
            const textNode = item.textNode;

            if (!textNode || !hasExternalLinkAttribute(textNode)) {
                continue;
            }

            this.isEnabled = false;
            return;
        }

        this.isEnabled = true;
    }
}
