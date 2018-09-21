// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectProps} from '../Select';
import Select from '../Select';

type Props<T> = SelectProps & {
    values: Array<T>,
    noneSelectedText: string,
    allSelectedText: string,
    onChange: (values: Array<T>) => void,
};

export default class MultiSelect<T> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        skin: 'default',
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
                let selectedValue = child.props.children;
                if (typeof selectedValue !== 'string') {
                    selectedValue = selectedValue.toString();
                }
                selectedValues.push(selectedValue);
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

    handleSelect = (value: T) => {
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
        const {children, icon, skin} = this.props;

        return (
            <Select
                closeOnSelect={false}
                displayValue={this.displayValue}
                icon={icon}
                isOptionSelected={this.isOptionSelected}
                onSelect={this.handleSelect}
                selectedVisualization="checkbox"
                skin={skin}
            >
                {children}
            </Select>
        );
    }
}
