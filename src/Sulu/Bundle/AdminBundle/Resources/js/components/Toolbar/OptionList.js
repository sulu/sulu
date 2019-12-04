// @flow
import classNames from 'classnames';
import React from 'react';
import type {ElementRef} from 'react';
import Option from './Option';
import type {Skin} from './types';
import optionListStyles from './optionList.scss';

type Props = {
    onClose?: () => void,
    onOptionClick: (option: Object) => void,
    optionListRef?: (ref: ElementRef<'ul'>) => void,
    options: Array<Object>,
    size?: string,
    skin?: Skin,
    style?: Object,
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

    setRef = (ref: ?ElementRef<'ul'>) => {
        const {optionListRef} = this.props;
        if (optionListRef && ref) {
            optionListRef(ref);
        }
    };

    render() {
        const {
            size,
            value,
            options,
            skin,
            style,
        } = this.props;
        const optionListClass = classNames(
            optionListStyles.optionList,
            optionListStyles[skin],
            {
                [optionListStyles[size]]: size,
            }
        );

        return (
            <ul
                className={optionListClass}
                ref={this.setRef}
                style={style}
            >
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
