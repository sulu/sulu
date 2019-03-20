// @flow
import Command from '@ckeditor/ckeditor5-core/src/command';
import {LINK_HREF_ATTRIBUTE, LINK_TARGET_ATTRIBUTE} from './constants';

export default class ExternalUnlinkCommand extends Command {
    execute() {
        this.editor.model.change((writer) => {
            const selection = this.editor.model.document.selection;
            const firstPosition = selection.getFirstPosition();
            const textNode = firstPosition.textNode || firstPosition.nodeBefore;

            writer.removeAttribute(LINK_HREF_ATTRIBUTE, textNode);
            writer.removeAttribute(LINK_TARGET_ATTRIBUTE, textNode);
        });
    }
}
