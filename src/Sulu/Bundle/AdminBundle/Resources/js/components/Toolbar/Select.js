// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import Popover from './Popover';
import type {SelectOption, Select as SelectProps} from './types';
import OptionList from './OptionList';

@observer
class Select<T: ?string | number> extends React.Component<SelectProps<T>> {
    static defaultProps = {
        showText: true,
    };

    @computed get selectedOption(): ?Object {
        return this.props.options.find((option) => {
            return option.value === this.props.value;
        });
    }

    handleOptionClick: (option: SelectOption<T>) => void = (option) => {
        this.props.onChange(option.value);
    };

    render() {
        const {
            className,
            disabled,
            icon,
            label,
            loading,
            options,
            showText,
            size,
            skin,
            value,
        } = this.props;

        const buttonValue = this.selectedOption ? this.selectedOption.label : label;

        return (
            <Popover
                className={className}
                disabled={disabled}
                icon={icon}
                label={showText ? buttonValue : undefined}
                loading={loading}
                size={size}
                skin={skin}
            >
                {(onClose) => (
                    <OptionList
                        onClose={onClose}
                        onOptionClick={this.handleOptionClick}
                        options={options}
                        size={size}
                        skin={skin}
                        value={value}
                    />
                )}
            </Popover>
        );
    }
}

export default Select;
