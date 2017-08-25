// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectProps} from '../Select/types';
import {GenericSelect, Option} from '../Select';

type Props = SelectProps & {
    values: Array<string>,
    label: string,
    onChange: (values: Array<string>) => void,
};

export default class MultiSelect extends React.PureComponent<Props> {
    static defaultProps = {
        values: [],
    };

    isOptionSelected = (option: Element<typeof Option>): boolean => {
        return this.props.values.includes(option.props.value);
    };

    handleSelect = (value: string) => {
        const newValues = [...this.props.values];
        const index = newValues.indexOf(value);
        if (index === -1) {
            newValues.push(value);
        } else {
            newValues.splice(index, 1);
        }
        this.props.onChange(newValues);
    };

    render() {
        const {icon, label, children} = this.props;

        return (
            <GenericSelect
                icon={icon}
                onSelect={this.handleSelect}
                closeOnSelect={false}
                labelText={label}
                selectedVisualization="checkbox"
                isOptionSelected={this.isOptionSelected}>
                {children}
            </GenericSelect>
        );
    }
}
