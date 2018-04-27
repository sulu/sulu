// @flow
import React from 'react';
import TextAreaComponent from '../../../components/TextArea';
import type {FieldTypeProps} from '../../../types';

export default class TextArea extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {error, onChange, onFinish, value} = this.props;

        return (
            <TextAreaComponent
                onChange={onChange}
                onBlur={onFinish}
                valid={!error}
                value={value}
            />
        );
    }
}
