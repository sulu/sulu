// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectProps} from '../GenericSelect/types';
import Option from '../Option';
import GenericSelect from '../GenericSelect';

type Props = SelectProps & {
    values: Array<string>,
    noneSelectedLabel: string,
    allSelectedLabel: string,
    onChange: (values: Array<string>) => void,
};

export default class MultiSelect extends React.PureComponent<Props> {
    static defaultProps = {
        values: [],
    };

    get labelText(): string {
        let selectedLabels = [];
        let countOptions = 0;
        React.Children.forEach(this.props.children, (child: any) => {
            if (child.type !== Option) {
                return;
            }
            countOptions += 1;
            if (this.isOptionSelected(child)) {
                selectedLabels.push(child.props.children);
            }
        });

        if (selectedLabels.length === 0) {
            return this.props.noneSelectedLabel;
        }
        if (selectedLabels.length === countOptions) {
            return this.props.allSelectedLabel;
        }

        return selectedLabels.join(', ');
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
                labelText={this.labelText}
                selectedVisualization="checkbox"
                isOptionSelected={this.isOptionSelected}>
                {children}
            </GenericSelect>
        );
    }
}
