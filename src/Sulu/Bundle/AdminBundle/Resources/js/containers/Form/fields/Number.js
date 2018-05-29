// @flow
import React from 'react';
import NumberComponent from '../../../components/Number';
import type {FieldTypeProps} from '../../../types';

export default class Number extends React.Component<FieldTypeProps<?number>> {
    render() {
        const {error, onChange, onFinish, value} = this.props;

        return (
            <NumberComponent
                onChange={onChange}
                onBlur={onFinish}
                valid={!error}
                value={value}
            />
        );
    }
}
