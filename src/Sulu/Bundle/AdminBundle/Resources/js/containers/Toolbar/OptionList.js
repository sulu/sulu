// @flow
import type {DropdownOptionConfig, SelectOptionConfig} from './types';
import Backdrop from '../../components/Backdrop';
import Option from './Option';
import React from 'react';
import classNames from 'classnames';
import optionListStyles from './optionList.scss';

export default class OptionList extends React.PureComponent {
    props: {|
        options: Array<DropdownOptionConfig | SelectOptionConfig>,
        value?: string | number,
        size?: string,
        onClick?: (value?: string | number) => void,
        onClose?: () => void,
    |};

    handleOptionClick = (value?: string | number) => {
        this.props.onClick(value);
    };

    handleBackdropClick = () => {
        this.props.onClose();
    };

    render() {
        const {
            size,
            value,
            options,
        } = this.props;
        const optionListClasses = classNames({
            [optionListStyles.optionList]: true,
            [optionListStyles[size]]: size,
        });

        return (
            <div>
                <ul className={optionListClasses}>
                    {
                        options.map((option, index) => {
                            const optionValue = option.value || index;
                            const isSelected = optionValue === value;
                            const handleClick = (selectedOptionValue?: string | number) => {
                                if (!option.onClick) {
                                    this.handleOptionClick(selectedOptionValue);
                                } else {
                                    option.onClick();
                                }

                                this.props.onClose();
                            };

                            return (
                                <Option
                                    key={index}
                                    size={size}
                                    value={optionValue}
                                    label={option.label}
                                    disabled={option.disabled}
                                    selected={isSelected}
                                    onClick={handleClick} />
                            );
                        })
                    }
                </ul>
                <Backdrop isOpen={true} onClick={this.handleBackdropClick} isVisible={false} />
            </div>
        );
    }
}
