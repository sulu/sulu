// @flow
import Command from '@ckeditor/ckeditor5-core/src/command';

export default class ExternalUnlinkCommand extends Command {
    attributesToRemove: Array<string>;

    constructor(editor: Object, attributeToRemove: Array<string>) {
        super(editor);

        this.attributesToRemove = attributeToRemove;
    }

    execute() {
        this.editor.model.change((writer) => {
            const selection = this.editor.model.document.selection;
            const firstPosition = selection.getFirstPosition();
            const textNode = firstPosition.textNode || firstPosition.nodeBefore;

            this.attributesToRemove.forEach((attributeToRemove) => {
                writer.removeAttribute(attributeToRemove, textNode);
            });
        });
    }
}
