// @flow
import classNames from 'classnames';
import React from 'react';
import type {ElementRef} from 'react';
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
    optionListRef?: (ref: ElementRef<'ul'>) => void,
    style?: Object,
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
