// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectProps} from '../Select';
import Select from '../Select';

type Props = SelectProps & {
    values: Array<string | number>,
    noneSelectedText: string,
    allSelectedText: string,
    onChange: (values: Array<string | number>) => void,
};

export default class MultiSelect extends React.PureComponent<Props> {
    static defaultProps = {
        values: [],
    };

    static Action = Select.Action;

    static Option = Select.Option;

    static Divider = Select.Divider;

    get displayValue(): string {
        const selectedValues = [];
        let countOptions = 0;

        React.Children.forEach(this.props.children, (child: any) => {
            if (child.type !== MultiSelect.Option) {
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

    isOptionSelected = (option: Element<typeof MultiSelect.Option>): boolean => {
        return this.props.values.includes(option.props.value);
    };

    handleSelect = (value: string | number) => {
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
            <Select
                closeOnSelect={false}
                displayValue={this.displayValue}
                icon={icon}
                isOptionSelected={this.isOptionSelected}
                onSelect={this.handleSelect}
                selectedVisualization="checkbox"
            >
                {children}
            </Select>
        );
    }
}
