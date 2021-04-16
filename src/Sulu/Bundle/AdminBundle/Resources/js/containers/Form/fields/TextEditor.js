// @flow
import React from 'react';
import {computed} from 'mobx';
import TextEditorContainer from '../../../containers/TextEditor';
import type {FieldTypeProps} from '../../../types';
import userStore from '../../../stores/userStore';

export default class TextEditor extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {disabled, formInspector, onChange, onFinish, schemaOptions, value} = this.props;

        const locale = formInspector.locale ? formInspector.locale : computed(() => userStore.contentLocale);

        return (
            <TextEditorContainer
                adapter="ckeditor5"
                disabled={!!disabled}
                locale={locale}
                onBlur={onFinish}
                onChange={onChange}
                options={schemaOptions}
                value={value}
            />
        );
    }
}
