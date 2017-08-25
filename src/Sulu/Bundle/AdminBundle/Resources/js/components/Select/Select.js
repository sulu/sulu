// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectProps} from './types';
import Option from './Option';
import GenericSelect from './GenericSelect';

type Props = SelectProps & {
    value?: string,
    onChange?: (value: string) => void,
};

export default class Select extends React.PureComponent<Props> {
    get labelText(): string {
        let label = '';
        React.Children.forEach(this.props.children, (child: any) => {
            if (child.type !== Option) {
                return;
            }
            if (!label || this.props.value === child.props.value) {
                label = child.props.children;
            }
        });

        return label;
    }

    isOptionSelected = (option: Element<typeof Option>): boolean => {
        return option.props.value === this.props.value && !option.props.disabled;
    };

    handleSelect = (value: string) => {
        if (this.props.onChange) {
            this.props.onChange(value);
        }
    };

    render() {
        const {icon, children} = this.props;

        return (
            <GenericSelect
                icon={icon}
                onSelect={this.handleSelect}
                labelText={this.labelText}
                isOptionSelected={this.isOptionSelected}>
                {children}
            </GenericSelect>
        );
    }
}
