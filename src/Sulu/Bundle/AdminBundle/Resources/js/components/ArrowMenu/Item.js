// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import itemStyles from './item.scss';

type Props = {
    value: *,
    children: string,
    active: boolean,
    disabled?: boolean,
    icon?: string,
    onClick?: (value: *) => void,
};

export default class Item extends React.PureComponent<Props> {
    handleButtonClick = () => {
        const {onClick, disabled, value} = this.props;

        if (disabled || !onClick) {
            return;
        }

        onClick(value);
    };

    static defaultProps = {
        active: false,
        disabled: false,
    };

    render() {
        const {
            children,
            active,
            icon,
            disabled,
        } = this.props;

        const itemClass = classNames(
            (disabled ? itemStyles.itemDisabled : itemStyles.item),
            {
                [itemStyles.active]: active,
            }
        );

        return (
            <div className={itemClass} onClick={this.handleButtonClick}>
                <span className={itemStyles.icon}>
                    {icon && active && <Icon className={itemStyles.icon} name={icon} />}
                </span>
                <span>
                    {children}
                </span>
            </div>
        );
    }
}
