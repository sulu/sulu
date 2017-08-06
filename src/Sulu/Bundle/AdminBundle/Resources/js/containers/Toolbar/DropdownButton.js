// @flow
import type {DropdownButtonType, OptionConfigType} from './types';
import {action, computed, observable} from 'mobx';
import Backdrop from '../../components/Backdrop';
import ButtonSizes from './buttonSizes';
import DefaultButton from './DefaultButton';
import DropdownOption from './DropdownOption';
import React from 'react';
import buttonStyles from './button.scss';
import classNames from 'classnames';
import dropdownStyles from './dropdown.scss';
import {observer} from 'mobx-react';

@observer
export default class ButtonDropdown extends React.PureComponent {
    props: DropdownButtonType;

    static defaultProps = {
        label: '',
        disabled: false,
        setValueOnChange: false,
    };

    @observable isOpen = this.props.isOpen;

    @action close = () => {
        this.isOpen = false;
    };

    @action toggle = () => {
        this.isOpen = !this.isOpen;
    };

    @observable selectedValue = this.props.value;

    @computed get selectedOption(): ?OptionConfigType {
        return this.props.options.find((option) => {
            return option.value === this.selectedValue;
        });
    }

    @action selectValue = (value: string) => {
        this.selectedValue = value;
    };

    componentWillReceiveProps = (nextProps: DropdownButtonType) => {
        const {disabled} = nextProps;

        if (disabled) {
            this.close();
        }
    };

    handleButtonClick = () => {
        this.toggle();
    };

    handleOnChange = (value: string) => {
        const {onChange, setValueOnChange} = this.props;

        if (!this.equalsSelectedOption(value)) {
            onChange(value);

            if (setValueOnChange) {
                this.selectValue(value);
            }
        }

        this.close();
    };

    handleBackdropClick = () => {
        this.close();
    };

    equalsSelectedOption = (value: string) => {
        if (!this.selectedOption) {
            return false;
        }

        return value === this.selectedOption.value;
    };

    render() {
        const {
            icon,
            size,
            label,
            options,
            disabled,
        } = this.props;
        const buttonSizeClass = size ? ButtonSizes.getClassName(size) : null;
        const dropdownClasses = classNames({
            [dropdownStyles.dropdown]: true,
            [dropdownStyles.isOpen]: this.isOpen,
            [dropdownStyles[buttonSizeClass]]: buttonSizeClass,
        });
        const buttonValue = this.selectedOption ? this.selectedOption.label : label;

        return (
            <div className={buttonStyles.buttonContainer}>
                <DefaultButton
                    icon={icon}
                    size={size}
                    disabled={disabled}
                    value={buttonValue}
                    onClick={this.handleButtonClick}
                    isActive={this.isOpen}
                    hasOptions={true} />
                <ul className={dropdownClasses}>
                    {
                        options.map((option) => (
                            <DropdownOption
                                key={option.value}
                                value={option.value}
                                label={option.label}
                                disabled={option.disabled}
                                onClick={this.handleOnChange}
                                selected={this.equalsSelectedOption(option.value)} />
                        ))
                    }
                </ul>
                <Backdrop isOpen={this.isOpen} onClick={this.handleBackdropClick} isVisible={false} />
            </div>
        );
    }
}
