// @flow
import {action, computed, observable} from 'mobx';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import React from 'react';
import type {SelectOption, Select as SelectProps} from './types';
import Button from './Button';
import OptionList from './OptionList';
import selectStyles from './select.scss';

@observer
export default class Select extends React.Component<SelectProps> {
    @observable open: boolean = false;

    @action close = () => {
        this.open = false;
    };

    @action toggle = () => {
        this.open = !this.open;
    };

    @computed get selectedOption(): ?Object {
        return this.props.options.find((option) => {
            return option.value === this.props.value;
        });
    }

    componentWillReceiveProps = (nextProps: SelectProps) => {
        const {disabled} = nextProps;

        if (disabled) {
            this.close();
        }
    };

    handleButtonClick = () => {
        this.toggle();
    };

    handleOptionClick = (option: SelectOption) => {
        this.props.onChange(option.value);
    };

    handleOptionListClose = () => {
        this.close();
    };

    render() {
        const {
            icon,
            size,
            value,
            label,
            options,
            disabled,
            loading,
            className,
            skin,
        } = this.props;
        const buttonValue = this.selectedOption ? this.selectedOption.label : label;
        const selectClass = classNames(
            className,
            selectStyles.select,
            selectStyles[skin],
            (size) ? selectStyles[size] : null
        );

        return (
            <div className={selectClass}>
                <Button
                    icon={icon}
                    skin={skin}
                    size={size}
                    disabled={disabled}
                    value={buttonValue}
                    onClick={this.handleButtonClick}
                    active={this.open}
                    hasOptions={true}
                    loading={loading}
                />
                {this.open &&
                    <OptionList
                        skin={skin}
                        size={size}
                        value={value}
                        options={options}
                        onOptionClick={this.handleOptionClick}
                        onClose={this.handleOptionListClose}
                    />
                }
            </div>
        );
    }
}
