// @flow
import React from 'react';
import type {Element} from 'react';
import type {SelectChildren} from './types';
import Option from './Option';
import GenericSelect from './GenericSelect';

type Props = {
    value?: string,
    onChange?: (value: string) => void,
    children: SelectChildren,
    icon?: string,
};

export default class Select extends React.PureComponent<Props> {
    getLabelText = (): string => {
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
    };

    optionIsSelected = (option: Element<typeof Option>): boolean => {
        return option.props.value === this.props.value && !option.props.disabled;
    };

    handleSelect = (value: string) => {
        if (this.props.onChange) {
            this.props.onChange(value);
        }
    };

    render() {
        return (
            <GenericSelect
                icon={this.props.icon}
                onSelect={this.handleSelect}
                getLabelText={this.getLabelText}
                optionIsSelected={this.optionIsSelected}>
                {this.props.children}
            </GenericSelect>
        );
    }
}
