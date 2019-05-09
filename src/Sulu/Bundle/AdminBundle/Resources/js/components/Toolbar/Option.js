// @flow
import classNames from 'classnames';
import React from 'react';
import Icon from '../Icon';
import type {Skin} from './types';
import optionStyles from './option.scss';

const ICON_CHECKMARK = 'su-check';

type Props = {
    disabled?: boolean,
    label: string | number,
    onClick: (value: Object) => void,
    selected?: boolean,
    size?: string,
    skin?: Skin,
    value: Object,
};

export default class Option extends React.PureComponent<Props> {
    handleOnClick = () => {
        const {onClick} = this.props;

        onClick(this.props.value);
    };

    render() {
        const {
            skin,
            size,
            label,
            selected,
            disabled,
        } = this.props;
        const optionClass = classNames(
            optionStyles.option,
            optionStyles[skin],
            {
                [optionStyles[size]]: size,
                [optionStyles.isSelected]: selected,
            }
        );

        return (
            <li className={optionClass}>
                <button
                    disabled={disabled}
                    onClick={this.handleOnClick}
                >
                    {selected &&
                        <Icon className={optionStyles.selectedIcon} name={ICON_CHECKMARK} />
                    }
                    {label}
                </button>
            </li>
        );
    }
}
