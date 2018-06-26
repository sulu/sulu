// @flow
import React from 'react';
import TextEditorContainer from '../../../containers/TextEditor';
import type {FieldTypeProps} from '../../../types';

export default class TextEditor extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {onChange, value} = this.props;

        return (
            <TextEditorContainer
                adapter="ckeditor5"
                onChange={onChange}
                value={value}
            />
        );
    }
}
