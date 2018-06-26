// @flow
import React from 'react';
import textEditorRegistry from './registries/TextEditorRegistry';

type Props = {
    adapter: string,
    onChange: (value: ?string) => void,
    value: ?string,
};

export default class TextEditor extends React.Component<Props> {
    render() {
        const {
            adapter,
            ...textEditorProps
        } = this.props;

        const TextEditorAdapter = textEditorRegistry.get(adapter);

        return <TextEditorAdapter {...textEditorProps} />;
    }
}
