// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import CheckboxComponent from '../../../components/Checkbox';
import Toggler from '../../../components/Toggler';
import Heading from './Heading';
import type {FieldTypeProps} from '../../../types';

@observer
class Checkbox extends React.Component<FieldTypeProps<boolean>> {
    @computed get schemaOptions() {
        return this.props.schemaOptions;
    }

    @computed get label() {
        return this.schemaOptions.label?.title;
    }

    @computed get skin() {
        return this.schemaOptions.skin?.value;
    }

    @computed get type() {
        return this.schemaOptions.type?.value;
    }

    constructor(props: FieldTypeProps<boolean>) {
        super(props);

        const {onChange, schemaOptions, value} = this.props;

        const {
            default_value: {
                value: defaultValue,
            } = {},
        } = schemaOptions;

        if (defaultValue === undefined || defaultValue === null) {
            return;
        }

        if (typeof defaultValue !== 'boolean') {
            throw new Error('The "default_value" schema option must be a boolean if given!');
        }

        if (value === undefined) {
            onChange(defaultValue, {isDefaultValue: true});
        }
    }

    handleChange = (checked: boolean) => {
        const {onChange, onFinish} = this.props;
        onChange(checked);
        onFinish();
    };

    handleHeadingChange = () => {};

    render() {
        const {
            disabled,
            value,
        } = this.props;

        const field = this.type === 'toggler'
            ? (
                <Toggler
                    checked={!!value}
                    disabled={!!disabled}
                    onChange={this.handleChange}
                >
                    {this.skin !== 'heading' && this.label}
                </Toggler>
            )
            : (
                <CheckboxComponent
                    checked={!!value}
                    disabled={!!disabled}
                    onChange={this.handleChange}
                >
                    {this.skin !== 'heading' && this.label}
                </CheckboxComponent>
            );

        if (this.skin === 'heading') {
            return (
                <Heading
                    {...this.props}
                    onChange={this.handleHeadingChange}
                    value={undefined}
                >
                    {field}
                </Heading>
            );
        }

        return field;
    }
}

export default Checkbox;
