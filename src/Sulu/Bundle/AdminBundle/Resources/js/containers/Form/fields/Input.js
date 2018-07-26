// @flow
import React from 'react';
import InputComponent from '../../../components/Input';
import type {FieldTypeProps} from '../../../types';

export default class Input extends React.Component<FieldTypeProps<?string>> {
    handleBlur = () => {
        this.props.onFinish();
    };

    render() {
        const {
            error,
            onChange,
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
            <InputComponent
                maxCharacters={maxCharacters ? parseInt(maxCharacters) : undefined}
                onChange={onChange}
                onBlur={this.handleBlur}
                valid={!error}
                value={value}
            />
        );
    }
}
