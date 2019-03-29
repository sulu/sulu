// @flow
import React from 'react';
import CheckboxComponent from '../../../components/Checkbox';
import Toggler from '../../../components/Toggler';
import type {FieldTypeProps} from '../../../types';

export default class Checkbox extends React.Component<FieldTypeProps<boolean>> {
    constructor(props: FieldTypeProps<boolean>) {
        super(props);

        const {onChange, schemaOptions, value} = this.props;

        if (!schemaOptions) {
            return;
        }

        const {
            default_value: {
                value: defaultValue,
            } = {},
        } = schemaOptions;

        if (defaultValue === undefined || defaultValue === null) {
            return;
        }

        if (typeof defaultValue !== 'boolean') {
            throw new Error('The "default_value" schema option must be a string or a number!');
        }

        if (value === undefined) {
            onChange(defaultValue);
        }
    }
    handleChange = (checked: boolean) => {
        const {onChange, onFinish} = this.props;
        onChange(checked);
        onFinish();
    };

    render() {
        const {
            disabled,
            schemaOptions: {
                label: {
                    title: label,
                } = {},
                type: {
                    value: type,
                } = {},
            } = {},
            value,
        } = this.props;

        if (type === 'toggler') {
            return (
                <Toggler
                    checked={!!value}
                    disabled={!!disabled}
                    onChange={this.handleChange}
                >
                    {label}
                </Toggler>
            );
        }

        return (
            <CheckboxComponent
                checked={!!value}
                disabled={!!disabled}
                onChange={this.handleChange}
            >
                {label}
            </CheckboxComponent>
        );
    }
}
