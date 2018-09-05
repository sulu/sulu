// @flow
import React from 'react';
import type {FieldTypeProps} from '../../../types';
import UrlComponent from '../../../components/Url';

export default class Url extends React.Component<FieldTypeProps<?string>> {
    handleBlur = () => {
        this.props.onFinish();
    };

    render() {
        const {
            error,
            onChange,
            schemaOptions: {
                schemes: {
                    value: schemes,
                } = {},
            } = {},
            value,
        } = this.props;

        if (!Array.isArray(schemes) || schemes.length === 0) {
            throw new Error('The "schemes" schema option must contain some values!');
        }

        const protocols = schemes.map((scheme) => {
            if (typeof scheme.name !== 'string') {
                throw new Error(
                    'Every schema in the "schemes" schemaOption must contain a string string name'
                );
            }
            return scheme.name;
        });

        return (
            <UrlComponent
                onBlur={this.handleBlur}
                onChange={onChange}
                protocols={protocols}
                valid={!error}
                value={value}
            />
        );
    }
}
