// @flow
import React from 'react';
import TextAreaComponent from '../../../components/TextArea';
import type {FieldTypeProps} from '../../../types';

export default class TextArea extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {
            error,
            onChange,
            onFinish,
            schemaOptions: {
                max_characters: {
                    value: maxCharacters,
                } = {},
            } = {},
            value,
        } = this.props;

        if (maxCharacters && isNaN(maxCharacters)) {
            throw new Error('The "max_characters" schema option must be a number!');
        }

        return (
            <TextAreaComponent
                maxCharacters={maxCharacters ? parseInt(maxCharacters) : undefined}
                onBlur={onFinish}
                onChange={onChange}
                valid={!error}
                value={value}
            />
        );
    }
}
