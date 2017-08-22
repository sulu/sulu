// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectChildren} from '../Select/types';
import {GenericSelect, Option} from '../Select';

type Props = {
    values: Array<string>,
    label: string,
    onChange: (values: Array<string>) => void,
    children: SelectChildren,
    icon?: string,
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
        return (
            <GenericSelect
                icon={this.props.icon}
                onSelect={this.handleSelect}
                closeOnSelect={false}
                labelText={this.props.label}
                selectedVisualization="checkbox"
                isOptionSelected={this.isOptionSelected}>
                {this.props.children}
            </GenericSelect>
        );
    }
}
