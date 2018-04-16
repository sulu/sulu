// @flow
import classNames from 'classnames';
import React from 'react';
import Icon from '../Icon';
import type {Skin} from './types';
import optionStyles from './option.scss';

const ICON_CHECKMARK = 'su-check';

type Props = {
    label: string | number,
    value: Object,
    onClick: (value: Object) => void,
    size?: string,
    skin?: Skin,
    selected?: boolean,
    disabled?: boolean,
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
                        <Icon name={ICON_CHECKMARK} className={optionStyles.selectedIcon} />
                    }
                    {label}
                </button>
            </li>
        );
    }
}
