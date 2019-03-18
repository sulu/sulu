// @flow
import Command from '@ckeditor/ckeditor5-core/src/command';
import {LINK_HREF_ATTRIBUTE, LINK_TARGET_ATTRIBUTE} from './utils';

export default class ExternalUnlinkCommand extends Command {
    execute() {
        this.editor.model.change((writer) => {
            const selection = this.editor.model.document.selection;
            const firstPosition = selection.getFirstPosition();

            writer.removeAttribute(LINK_HREF_ATTRIBUTE, firstPosition.textNode);
            writer.removeAttribute(LINK_TARGET_ATTRIBUTE, firstPosition.textNode);
        });
    }
}
