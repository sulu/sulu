// @flow
import classNames from 'classnames';
import React from 'react';
import Backdrop from '../Backdrop';
import Option from './Option';
import type {Skin} from './types';
import optionListStyles from './optionList.scss';

type Props = {
    onOptionClick: (option: Object) => void,
    value?: string | number,
    size?: string,
    skin?: Skin,
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
            skin,
        } = this.props;
        const optionListClass = classNames(
            optionListStyles.optionList,
            optionListStyles[skin],
            {
                [optionListStyles[size]]: size,
            }
        );

        return (
            <div>
                <Backdrop local={true} onClick={this.handleBackdropClick} open={true} visible={false} />
                <ul className={optionListClass}>
                    {
                        options.map((option, index: number) => {
                            const selected = option.value ? option.value === value : false;

                            return (
                                <Option
                                    disabled={option.disabled}
                                    key={index}
                                    label={option.label}
                                    onClick={this.handleOptionClick}
                                    selected={selected}
                                    size={size}
                                    skin={skin}
                                    value={option}
                                />
                            );
                        })
                    }
                </ul>
            </div>
        );
    }
}
