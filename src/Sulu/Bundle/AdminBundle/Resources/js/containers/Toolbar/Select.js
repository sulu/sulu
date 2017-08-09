// @flow
import {action, computed, observable} from 'mobx';
import Button from './Button';
import OptionList from './OptionList';
import React from 'react';
import type {OptionConfig, SelectConfig} from './types';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import selectStyles from './select.scss';

@observer
export default class Select extends React.PureComponent {
    props: SelectConfig;

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

    @computed get selectedOption(): ?OptionConfig {
        return this.props.options.find((option) => {
            return option.value === this.props.value;
        });
    }

    componentWillReceiveProps = (nextProps: SelectType) => {
        const {disabled} = nextProps;

        if (disabled) {
            this.close();
        }
    };

    handleButtonClick = () => {
        this.toggle();
    };

    handleOptionListClick = (value: string) => {
        this.props.onChange(value);
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
                        onClose={this.handleOptionListClose} />
                }
            </div>
        );
    }
}
