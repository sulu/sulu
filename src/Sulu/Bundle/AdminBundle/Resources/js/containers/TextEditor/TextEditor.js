// @flow
import React from 'react';
import type {TextEditorProps} from './types';
import textEditorRegistry from './registries/TextEditorRegistry';

// TODO: make configurable
const DEFAULT_TEXT_EDITOR = 'draft';

type Props = TextEditorProps & {
    adapter: string,
};

export default class TextEditor extends React.Component<Props> {
    render() {
        const {
            adapter,
            ...implementationProps
        } = this.props;

        let textEditor = DEFAULT_TEXT_EDITOR;

        if (adapter && textEditorRegistry.has(adapter)) {
            textEditor = adapter;
        }

        const Implementation = textEditorRegistry.get(textEditor);

        return <Implementation {...implementationProps} />;
    }
}
