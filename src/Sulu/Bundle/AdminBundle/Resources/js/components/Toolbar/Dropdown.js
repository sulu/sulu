// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import Popover from '../Popover';
import type {DropdownOption, Dropdown as DropdownProps} from './types';
import Button from './Button';
import OptionList from './OptionList';
import dropdownStyles from './dropdown.scss';

@observer
class Dropdown extends React.Component<DropdownProps> {
    @observable open: boolean = false;

    static defaultProps = {
        showText: true,
    };

    @observable buttonRef: ?ElementRef<'button'>;

    @action setButtonRef = (ref: ?ElementRef<'button'>) => {
        if (ref) {
            this.buttonRef = ref;
        }
    };

    @action close = () => {
        this.open = false;
    };

    @action toggle = () => {
        this.open = !this.open;
    };

    componentDidUpdate() {
        const {disabled} = this.props;

        if (disabled) {
            this.close();
        }
    }

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
            showText,
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
                    buttonRef={this.setButtonRef}
                    disabled={disabled || allChildrenDisabled}
                    hasOptions={true}
                    icon={icon}
                    label={showText ? label : undefined}
                    loading={loading}
                    onClick={this.handleButtonClick}
                    size={size}
                    skin={skin}
                />
                <Popover
                    anchorElement={this.buttonRef}
                    onClose={this.handleOptionListClose}
                    open={this.open}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <OptionList
                                onClose={this.handleOptionListClose}
                                onOptionClick={this.handleOptionListClick}
                                optionListRef={setPopoverElementRef}
                                options={options}
                                skin={skin}
                                style={popoverStyle}
                            />
                        )
                    }
                </Popover>
            </div>
        );
    }
}

export default Dropdown;
