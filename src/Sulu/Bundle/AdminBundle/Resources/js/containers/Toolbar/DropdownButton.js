// @flow
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Backdrop from '../../components/Backdrop';
import type {DropdownButtonType} from './types';
import DefaultButton from './DefaultButton';
import React from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import buttonStyles from './button.scss';
import dropdownStyles from './dropdown.scss';

@observer
export default class ButtonDropdown extends React.PureComponent {
    props: DropdownButtonType;

    static defaultProps = {
        disabled: false,
        defaultValue: '',
        setValueOnSelect: false,
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

    render() {
        const {
            icon,
            value,
            options,
            onChange,
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
                    value={value || defaultValue} 
                    onClick={this.toggle}
                    isActive={this.isOpen} 
                    hasOptions={true} 
                />
                <ul className={dropdownClasses}>
                    {
                        options.map((option) => (
                            <li key={option.value}>
                                <button className={dropdownStyles.option} onClick={() => onChange(option)}>
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
