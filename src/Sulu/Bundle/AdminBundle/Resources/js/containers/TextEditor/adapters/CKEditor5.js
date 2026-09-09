// @flow
import React from 'react';
import CKEditor5Component from '../../CKEditor5';
import type {TextEditorAdapterProps} from '../types';

export default class CKEditor5 extends React.Component<TextEditorAdapterProps> {
    render() {
        const {
            config,
            disabled,
            locale,
            onBlur,
            onChange,
            onFocus,
            value,
        } = this.props;

        return (
            <CKEditor5Component
                config={config}
                disabled={disabled}
                locale={locale}
                onBlur={onBlur}
                onChange={onChange}
                onFocus={onFocus}
                value={value}
            />
        );
    }
}
