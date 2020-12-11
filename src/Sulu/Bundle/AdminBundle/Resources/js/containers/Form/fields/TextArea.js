// @flow
import React from 'react';
import log from 'loglevel';
import TextAreaComponent from '../../../components/TextArea';
import type {FieldTypeProps} from '../../../types';

export default class TextArea extends React.Component<FieldTypeProps<?string>> {
    render() {
        const {
            dataPath,
            error,
            onChange,
            onFinish,
            disabled,
            schemaOptions: {
                max_characters: {
                    value: maxCharacters,
                } = {},
                softMaxLength: {
                    value: softMaxLength,
                } = {},
            } = {},
            value,
        } = this.props;

        if (maxCharacters !== undefined) {
            log.warn(
                'The "max_characters" schema option is deprecated since version 2.3 and will be removed. ' +
                'Use the "softMaxLength" option instead.'
            );
        }

        if (maxCharacters !== undefined && isNaN(maxCharacters)) {
            throw new Error('The "max_characters" schema option must be a number!');
        }

        if (softMaxLength !== undefined && isNaN(softMaxLength)) {
            throw new Error('The "softMaxLength" schema option must be a number!');
        }

        const evaluatedSoftMaxLength = softMaxLength || maxCharacters;

        return (
            <TextAreaComponent
                disabled={!!disabled}
                id={dataPath}
                maxCharacters={evaluatedSoftMaxLength ? parseInt(evaluatedSoftMaxLength) : undefined}
                onBlur={onFinish}
                onChange={onChange}
                valid={!error}
                value={value}
            />
        );
    }
}
