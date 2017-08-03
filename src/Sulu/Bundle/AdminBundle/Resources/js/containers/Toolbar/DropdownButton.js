// @flow
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Backdrop from '../../components/Backdrop';
import type {DropdownButtonType, OptionType} from './types';
import DefaultButton from './DefaultButton';
import React from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import buttonStyles from './button.scss';
import dropdownStyles from './dropdown.scss';

const ICON_CHECKMARK = 'check';

@observer
export default class ButtonDropdown extends React.PureComponent {
    props: DropdownButtonType;

    static defaultProps = {
        disabled: false,
        defaultValue: '',
        setValueOnChange: false,
    };

    @observable value = this.props.value;

    @action updateValue = (value) => {
        this.value = value;
    };

    @observable isOpen = this.props.isOpen;

    @action close = () => {
        this.isOpen = false;
    };

    @action toggle = () => {
        this.isOpen = !this.isOpen;
    };

    componentWillReceiveProps = (nextProps: DropdownButtonType) => {
        const {disabled} = nextProps;

        if (disabled) {
            this.close();
        }
    };

    handleOnChange = (option: OptionType) => {
        const {onChange, setValueOnChange} = this.props;

        onChange(option);

        if (setValueOnChange) {
            this.updateValue(option.value);
        }

        this.close();
    };

    render() {
        const {
            icon,
            value,
            options,
            disabled,
            defaultValue,
        } = this.props;
        const dropdownClasses = classNames({
            [dropdownStyles.dropdown]: true,
            [dropdownStyles.isOpen]: this.isOpen,
        });

        return (
            <div className={buttonStyles.buttonContainer}>
                <DefaultButton 
                    icon={icon}
                    disabled={disabled}
                    value={this.value || defaultValue} 
                    onClick={this.toggle}
                    isActive={this.isOpen} 
                    hasOptions={true} 
                />
                <ul className={dropdownClasses}>
                    {
                        options.map((option) => (
                            <li key={option.value} className={classNames({
                                [dropdownStyles.isSelected]: option.selected
                            })}>
                                {option.selected &&
                                    <Icon name={ICON_CHECKMARK} className={dropdownStyles.optionSelectedIcon} />
                                }
                                <button 
                                    disabled={option.disabled}
                                    onClick={() => this.handleOnChange(option)}
                                    className={dropdownStyles.option}
                                >
                                    {option.value}
                                </button>
                            </li>
                        ))
                    }
                </ul>
                <Backdrop isOpen={this.isOpen} onClick={this.close} opacity={0} />
            </div>
        );
    }
}
