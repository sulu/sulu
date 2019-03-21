// @flow
import React from 'react';
import TextEditorContainer from '../../../containers/TextEditor';
import type {FieldTypeProps} from '../../../types';

export default class TextEditor extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {disabled, formInspector, onChange, onFinish, schemaOptions, value} = this.props;

        return (
            <TextEditorContainer
                adapter="ckeditor5"
                disabled={!!disabled}
                locale={formInspector.locale}
                onBlur={onFinish}
                onChange={onChange}
                options={schemaOptions}
                value={value}
            />
        );
    }
}
