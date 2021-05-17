// @flow
import React from 'react';
import Select from '../Select';
import {translate} from '../../utils/Translator';
import type {Element} from 'react';
import type {SelectProps} from '../Select';

type Props<T: string | number> = {|
    ...SelectProps<T>,
    allSelectedText?: string,
    noneSelectedText?: string,
    onChange: (values: Array<T>) => void,
    onClose?: () => void,
    values: Array<T>,
|};

export default class MultiSelect<T: string | number> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        disabled: false,
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
            const {noneSelectedText} = this.props;

            return noneSelectedText ? noneSelectedText : translate('sulu_admin.none_selected');
        }

        if (selectedValues.length === countOptions) {
            const {allSelectedText} = this.props;

            return allSelectedText ? allSelectedText : translate('sulu_admin.all_selected');
        }

        return selectedValues.join(', ');
    }

    // TODO: Remove explicit type annotation when flow bug is fixed
    // https://github.com/facebook/flow/issues/6978
    isOptionSelected: (option: Element<Class<MultiSelect.Option<T>>>) => boolean = (option) => {
        return this.props.values.includes(option.props.value);
    };

    // TODO: Remove explicit type annotation when flow bug is fixed
    // https://github.com/facebook/flow/issues/6978
    handleSelect: (value: T) => void = (value: T) => {
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
        const {children, disabled, icon, onClose, skin} = this.props;

        return (
            <Select
                closeOnSelect={false}
                disabled={disabled}
                displayValue={this.displayValue}
                icon={icon}
                isOptionSelected={this.isOptionSelected}
                onClose={onClose}
                onSelect={this.handleSelect}
                selectedVisualization="checkbox"
                skin={skin}
            >
                {children}
            </Select>
        );
    }
}
