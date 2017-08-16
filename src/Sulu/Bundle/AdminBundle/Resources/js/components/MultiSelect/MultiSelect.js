// @flow
import React from 'react';
import {GenericSelect, Option, Divider} from '../Select';

export default class MultiSelect extends React.PureComponent {
    props: {
        values: Array<string>,
        label: string,
        onChange: (values: Array<string>) => void,
        children: Array<Option | Divider>,
        icon?: string,
    };

    static defaultProps = {
        children: [],
        values: [],
    };

    optionIsSelected = (option: React.Element<*>): boolean => {
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
