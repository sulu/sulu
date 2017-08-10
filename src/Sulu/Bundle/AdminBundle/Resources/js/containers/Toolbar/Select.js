// @flow
import type {SelectOption, Select as SelectProps} from './types';
import {action, computed, observable} from 'mobx';
import Button from './Button';
import OptionList from './OptionList';
import React from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import selectStyles from './select.scss';

@observer
export default class Select extends React.PureComponent {
    props: SelectProps;

    @observable isOpen: boolean = false;

    @action open = () => {
        this.isOpen = true;
    };

    @action close = () => {
        this.isOpen = false;
    };

    @action toggle = () => {
        this.isOpen = !this.isOpen;
    };

    @computed get selectedOption(): ?any {
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

    handleOptionListClick = (option: SelectOption) => {
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
        } = this.props;
        const buttonValue = this.selectedOption ? this.selectedOption.label : label;
        const selectClasses = classNames({
            [selectStyles.select]: true,
            [selectStyles[size]]: size,
        });

        return (
            <div className={selectClasses}>
                <Button
                    icon={icon}
                    size={size}
                    disabled={disabled}
                    value={buttonValue}
                    onClick={this.handleButtonClick}
                    isActive={this.isOpen}
                    hasOptions={true} />
                {this.isOpen &&
                    <OptionList
                        size={size}
                        value={value}
                        options={options}
                        onClick={this.handleOptionListClick}
                        onRequestClose={this.handleOptionListClose} />
                }
            </div>
        );
    }
}
