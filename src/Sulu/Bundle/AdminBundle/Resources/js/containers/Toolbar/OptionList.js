// @flow
import Backdrop from '../../components/Backdrop';
import Option from './Option';
import type {OptionListConfig} from './types';
import React from 'react';
import classNames from 'classnames';
import optionListStyles from './optionList.scss';

export default class OptionList extends React.PureComponent {
    props: OptionListConfig;

    static defaultProps = {
        onClick: () => {},
    };

    handleOptionClick = (value: string) => {
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
                            const handleClick = (selectedOptionValue: string) => {
                                (option.onClick || this.handleOptionClick)(selectedOptionValue);

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
