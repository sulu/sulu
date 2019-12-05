// @flow
import classNames from 'classnames';
import React from 'react';
import Option from './Option';
import type {Skin} from './types';
import optionListStyles from './optionList.scss';

type Props = {
    onClose?: () => void,
    onOptionClick: (option: Object) => void,
    options: Array<Object>,
    size?: string,
    skin?: Skin,
    value?: string | number,
};

export default class OptionList extends React.PureComponent<Props> {
    handleOptionClick = (option: Object) => {
        const {onClose, onOptionClick} = this.props;
        if (onOptionClick) {
            onOptionClick(option);
        }

        if (onClose) {
            onClose();
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
        );
    }
}
