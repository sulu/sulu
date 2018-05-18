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
export default class Dropdown extends React.Component<DropdownProps> {
    @observable open = false;

    @action close = () => {
        this.open = false;
    };

    @action toggle = () => {
        this.open = !this.open;
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
            skin,
            label,
            options,
            disabled,
            loading,
        } = this.props;
        const dropdownClass = classNames(
            dropdownStyles.dropdown,
            {
                [dropdownStyles[size]]: size,
            }
        );

        const allChildrenDisabled = options.every((option) => option.disabled);

        return (
            <div className={dropdownClass}>
                <Button
                    active={this.open}
                    disabled={disabled || allChildrenDisabled}
                    hasOptions={true}
                    icon={icon}
                    loading={loading}
                    onClick={this.handleButtonClick}
                    size={size}
                    skin={skin}
                    value={label}
                />
                {this.open &&
                    <OptionList
                        onClose={this.handleOptionListClose}
                        onOptionClick={this.handleOptionListClick}
                        options={options}
                        skin={skin}
                    />
                }
            </div>
        );
    }
}
