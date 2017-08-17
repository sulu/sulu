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

    optionIsSelected = (option: Element<typeof Option>): boolean => {
        return this.props.values.indexOf(option.props.value) !== -1;
    };

    handleSelect = (value: string) => {
        let newValues = [...this.props.values];
        const index = newValues.indexOf(value);
        if (index === -1) {
            newValues.push(value);
        } else {
            newValues.splice(index, 1);
        }
        this.props.onChange(newValues);
    };

    getLabelText = () => this.props.label;

    render() {
        return (
            <GenericSelect
                icon={this.props.icon}
                onSelect={this.handleSelect}
                closeOnSelect={false}
                getLabelText={this.getLabelText}
                selectedVisualization="checkbox"
                optionIsSelected={this.optionIsSelected}>
                {this.props.children}
            </GenericSelect>
        );
    }
}
