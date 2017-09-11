// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectProps} from '../GenericSelect/types';
import GenericSelect, {Option} from '../GenericSelect';

type Props = SelectProps & {
    values: Array<string>,
    noneSelectedText: string,
    allSelectedText: string,
    onChange: (values: Array<string>) => void,
};

export default class MultiSelect extends React.PureComponent<Props> {
    static defaultProps = {
        values: [],
    };

    get displayValue(): string {
        let selectedValues = [];
        let countOptions = 0;

        React.Children.forEach(this.props.children, (child: any) => {
            if (child.type !== Option) {
                return;
            }

            countOptions += 1;

            if (this.isOptionSelected(child)) {
                selectedValues.push(child.props.children);
            }
        });

        if (selectedValues.length === 0) {
            return this.props.noneSelectedText;
        }

        if (selectedValues.length === countOptions) {
            return this.props.allSelectedText;
        }

        return selectedValues.join(', ');
    }

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
        const {icon, children} = this.props;

        return (
            <GenericSelect
                icon={icon}
                onSelect={this.handleSelect}
                closeOnSelect={false}
                displayValue={this.displayValue}
                selectedVisualization="checkbox"
                isOptionSelected={this.isOptionSelected}
            >
                {children}
            </GenericSelect>
        );
    }
}
