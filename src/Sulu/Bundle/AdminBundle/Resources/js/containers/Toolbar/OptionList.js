// @flow
import classNames from 'classnames';
import React from 'react';
import Backdrop from '../../components/Backdrop';
import Option from './Option';
import optionListStyles from './optionList.scss';

type Props = {
    onOptionClick: (option: Object) => void,
    value?: string | number,
    size?: string,
    onClose?: () => void,
    options: Array<Object>,
};

export default class OptionList extends React.PureComponent<Props> {
    handleOptionClick = (option: Object) => {
        if (this.props.onOptionClick) {
            this.props.onOptionClick(option);
        }

        if (this.props.onClose) {
            this.props.onClose();
        }
    };

    handleBackdropClick = () => {
        if (this.props.onClose) {
            this.props.onClose();
        }
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
                        options.map((option, index: number) => {
                            const isSelected = option.value ? option.value === value : false;

                            return (
                                <Option
                                    key={index}
                                    size={size}
                                    value={option}
                                    label={option.label}
                                    disabled={option.disabled}
                                    selected={isSelected}
                                    onClick={this.handleOptionClick} />
                            );
                        })
                    }
                </ul>
                <Backdrop isOpen={true} onClick={this.handleBackdropClick} isVisible={false} />
            </div>
        );
    }
}
