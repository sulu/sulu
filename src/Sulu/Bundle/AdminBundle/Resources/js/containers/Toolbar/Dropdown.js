// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import React from 'react';
import type {DropdownOption, Dropdown as DropdownProps} from './types';
import Button from './Button';
import OptionList from './OptionList';
import dropdownStyles from './dropdown.scss';

@observer
export default class Dropdown extends React.PureComponent<DropdownProps> {
    @observable isOpen = false;

    @action open = () => {
        this.isOpen = true;
    };

    @action close = () => {
        this.isOpen = false;
    };

    @action toggle = () => {
        this.isOpen = !this.isOpen;
    };

    componentWillReceiveProps = (nextProps: DropdownProps) => {
        const {disabled} = nextProps;

        if (disabled) {
            this.close();
        }
    };

    handleButtonClick = () => {
        this.toggle();
    };

    handleOptionListClick = (option: DropdownOption) => {
        if (option.onClick) {
            option.onClick();
        }
    };

    handleOptionListClose = () => {
        this.close();
    };

    render() {
        const {
            icon,
            size,
            label,
            options,
            disabled,
        } = this.props;
        const dropdownClasses = classNames({
            [dropdownStyles.dropdown]: true,
            [dropdownStyles[size]]: size,
        });

        return (
            <div className={dropdownClasses}>
                <Button
                    icon={icon}
                    size={size}
                    disabled={disabled}
                    value={label}
                    onClick={this.handleButtonClick}
                    isActive={this.isOpen}
                    hasOptions={true} />
                {this.isOpen &&
                    <OptionList
                        options={options}
                        onOptionClick={this.handleOptionListClick}
                        onRequestClose={this.handleOptionListClose} />
                }
            </div>
        );
    }
}
