// @flow
import React from 'react';
import textEditorRegistry from './registries/TextEditorRegistry';
import type {TextEditorProps} from './types';

type Props = {|
    ...TextEditorProps,
    adapter: string,
|};

export default class TextEditor extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    render() {
        const {
            adapter,
            ...textEditorProps
        } = this.props;

        const TextEditorAdapter = textEditorRegistry.get(adapter);

        return <TextEditorAdapter {...textEditorProps} />;
    }
}
