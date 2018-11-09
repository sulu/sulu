// @flow
import Command from '@ckeditor/ckeditor5-core/src/command';
import findLinkRange from './findlinkrange';

export default function(internalAttributes: string[]) {
    /*
     * The unlink command. It is used by the Link plugin.
     */
    return class UnlinkCommand extends Command {
        refresh() {
            this.isEnabled = !!internalAttributes.find((attr) =>
                this.editor.model.document.selection.hasAttribute(attr)
            );
        }

        /*
         * Executes the command.
         *
         * When the selection is collapsed, removes the `linkHref` attribute from each node with the same `linkHref`
         * attribute value.
         * When the selection is non-collapsed, removes the `linkHref` attribute from each node in selected ranges.
         */
        execute() {
            const model = this.editor.model;
            const selection = model.document.selection;

            model.change((writer) => {
                // Get ranges to unlink.
                const internalAttribute = internalAttributes.find((attr) =>
                    selection.hasAttribute(attr)
                ) || '';
                const rangesToUnlink = selection.isCollapsed
                    ? [
                        findLinkRange(
                            selection.getFirstPosition(),
                            selection.getAttribute(internalAttribute),
                            internalAttribute
                        ),
                    ]
                    : selection.getRanges();

                // Remove `linkHref` attribute from specified ranges.
                for (const range of rangesToUnlink) {
                    writer.removeAttribute(internalAttribute, range);
                }
            });
        }
    };
}
