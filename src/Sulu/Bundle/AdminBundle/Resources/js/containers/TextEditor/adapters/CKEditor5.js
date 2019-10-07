// @flow
import React from 'react';
import CKEditor5Component from '../../CKEditor5';
import type {TextEditorProps} from '../types';

export default class CKEditor5 extends React.Component<TextEditorProps> {
    render() {
        const {
            disabled,
            locale,
            onBlur,
            onChange,
            options,
            value,
        } = this.props;

        const formatOptionValues = options && options.formats ? options.formats.value : [];

        if (!Array.isArray(formatOptionValues)) {
            throw new Error('The passed "formats" must be an array of strings');
        }

        const formats = formatOptionValues.length
            ? formatOptionValues.map((format) => {
                if (typeof format.name !== 'string') {
                    throw new Error('The name property of the passed "formats" must be strings!');
                }
                return format.name;
            })
            : undefined;

        return (
            <CKEditor5Component
                disabled={disabled}
                formats={formats}
                locale={locale}
                onBlur={onBlur}
                onChange={onChange}
                value={value}
            />
        );
    }
}
