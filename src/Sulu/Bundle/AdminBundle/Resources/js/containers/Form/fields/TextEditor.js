// @flow
import React from 'react';
import TextEditorComponent from '../../../containers/TextEditor';
import type {FieldTypeProps} from '../../../types';

export default class TextEditor extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {error, value, onChange, onFinish, schemaOptions} = this.props;

        let adapter = '';
        if (schemaOptions && schemaOptions.textEditor) {
            adapter = schemaOptions.textEditor;
        }

        return (
            <TextEditorComponent
                adapter={adapter}
                onChange={onChange}
                onBlur={onFinish}
                value={value}
                valid={!error}
            />
        );
    }
}
