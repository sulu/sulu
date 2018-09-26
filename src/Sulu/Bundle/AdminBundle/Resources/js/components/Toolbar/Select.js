// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import Popover from '../Popover';
import type {SelectOption, Select as SelectProps} from './types';
import Button from './Button';
import OptionList from './OptionList';
import selectStyles from './select.scss';

@observer
export default class Select extends React.Component<SelectProps> {
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
            showText,
        } = this.props;
        const buttonValue = this.selectedOption ? this.selectedOption.label : label;
        const selectClass = classNames(
            className,
            selectStyles.select,
            {
                [selectStyles[size]]: size,
                [selectStyles[skin]]: skin,
            }
        );

        return (
            <div className={selectClass}>
                <Button
                    active={this.open}
                    buttonRef={this.setButtonRef}
                    disabled={disabled}
                    hasOptions={true}
                    icon={icon}
                    loading={loading}
                    onClick={this.handleButtonClick}
                    size={size}
                    skin={skin}
                    value={showText ? buttonValue : undefined}
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
                                onOptionClick={this.handleOptionClick}
                                optionListRef={setPopoverElementRef}
                                options={options}
                                size={size}
                                skin={skin}
                                style={popoverStyle}
                                value={value}
                            />
                        )
                    }
                </Popover>
            </div>
        );
    }
}
