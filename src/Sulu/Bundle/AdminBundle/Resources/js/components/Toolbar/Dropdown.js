// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {DropdownOption, Dropdown as DropdownProps} from './types';
import Popover from './Popover';
import OptionList from './OptionList';

@observer
class Dropdown extends React.Component<DropdownProps> {
    static defaultProps = {
        showText: true,
    };

    handleOptionListClick = (option: DropdownOption) => {
        if (option.onClick) {
            option.onClick();
        }
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

        const allChildrenDisabled = options.every((option) => option.disabled);

        return (
            <Popover
                disabled={disabled || allChildrenDisabled}
                icon={icon}
                label={showText ? label : undefined}
                loading={loading}
                size={size}
                skin={skin}
            >
                {(onClose) => (
                    <OptionList
                        onClose={onClose}
                        onOptionClick={this.handleOptionListClick}
                        options={options}
                        skin={skin}
                    />
                )}
            </Popover>
        );
    }
}

export default Dropdown;
