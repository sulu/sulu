// @flow
import classNames from 'classnames';
import React from 'react';
import Icon from '../Icon';
import optionStyles from './option.scss';

const ICON_CHECKMARK = 'su-checkmark';

type Props = {
    label: string | number,
    value: Object,
    onClick: (value: Object) => void,
    size?: string,
    skin?: string,
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
