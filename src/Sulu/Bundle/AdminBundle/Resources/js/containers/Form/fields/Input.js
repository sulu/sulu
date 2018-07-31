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
                max_segments: {
                    value: maxSegments,
                } = {},
                segment_delimiter: {
                    value: segmentDelimiter,
                } = {},
            } = {},
            value,
        } = this.props;

        if (maxCharacters !== undefined && isNaN(maxCharacters)) {
            throw new Error('The "max_characters" schema option must be a number!');
        }

        if (maxSegments !== undefined && isNaN(maxSegments)) {
            throw new Error('The "max_segments" schema option must be a number!');
        }

        if (segmentDelimiter !== undefined && typeof segmentDelimiter !== 'string') {
            throw new Error('The "segment_delimiter" schema option must be a string!');
        }

        return (
            <InputComponent
                maxCharacters={maxCharacters ? parseInt(maxCharacters) : undefined}
                maxSegments={maxSegments ? parseInt(maxSegments) : undefined}
                onChange={onChange}
                onBlur={this.handleBlur}
                segmentDelimiter={segmentDelimiter}
                valid={!error}
                value={value}
            />
        );
    }
}
