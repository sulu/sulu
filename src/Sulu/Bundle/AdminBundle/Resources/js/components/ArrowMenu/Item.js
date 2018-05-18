// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import itemStyles from './item.scss';

type Props = {
    value: *,
    children: string,
    active: boolean,
    icon?: string,
    onClick?: (value: *) => void,
};

export default class Item extends React.PureComponent<Props> {
    handleButtonClick = () => {
        const {onClick, value} = this.props;

        if (!onClick) {
            return;
        }

        onClick(value);
    };

    static defaultProps = {
        active: false,
    };

    render() {
        const {
            children,
            active,
            icon,
        } = this.props;

        const itemClass = classNames(
            itemStyles.item,
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
