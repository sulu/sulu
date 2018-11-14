// @flow
import React from 'react';
import TextEditorContainer from '../../../containers/TextEditor';
import type {FieldTypeProps} from '../../../types';

export default class TextEditor extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {disabled, onChange, onFinish, value} = this.props;

        return (
            <TextEditorContainer
                adapter="ckeditor5"
                disabled={!!disabled}
                onBlur={onFinish}
                onChange={onChange}
                value={value}
            />
        );
    }
}
