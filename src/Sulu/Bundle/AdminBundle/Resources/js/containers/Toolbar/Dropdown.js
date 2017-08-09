// @flow
import {action, observable} from 'mobx';
import Button from './Button';
import OptionList from './OptionList';
import type {DropdownOptionConfig} from './types';
import React from 'react';
import classNames from 'classnames';
import dropdownStyles from './dropdown.scss';
import {observer} from 'mobx-react';

type DropdownProps = {|
    options: Array<DropdownOptionConfig>,
    label?: string | number,
    icon?: string,
    size?: string,
    disabled?: boolean,
|};

@observer
export default class Dropdown extends React.PureComponent {
    props: DropdownProps;

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

    componentWillReceiveProps = (nextProps: DropdownProps) => {
        const {disabled} = nextProps;

        if (disabled) {
            this.close();
        }
    };

    handleButtonClick = () => {
        this.toggle();
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
                        onClose={this.handleOptionListClose} />
                }
            </div>
        );
    }
}
