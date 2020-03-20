// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import chipStyles from './chip.scss';

type Props<T> = {|
    children: Node,
    disabled: boolean,
    onClick?: (value: T) => void,
    onDelete?: (value: T) => void,
    size: 'small' | 'medium',
    skin: 'primary' | 'secondary',
    value: T,
|};

export default class Chip<T> extends React.Component<Props<T>> {
    static defaultProps = {
        disabled: false,
        size: 'small',
        skin: 'secondary',
    };

    handleClick = () => {
        const {onClick, value} = this.props;

        if (onClick) {
            onClick(value);
        }
    };

    handleDelete = () => {
        const {onDelete, value} = this.props;

        if (onDelete) {
            onDelete(value);
        }
    };

    render() {
        const {children, disabled, onClick, onDelete, size, skin} = this.props;

        const chipClass = classNames(
            chipStyles.chip,
            chipStyles[skin],
            chipStyles[size],
            {
                [chipStyles.disabled]: disabled,
                [chipStyles.clickable]: !!onClick,
            }
        );

        return (
            <button className={chipClass} onClick={this.handleClick}>
                {children}
                {!disabled && onDelete &&
                    <Icon className={chipStyles.icon} name="su-times" onClick={this.handleDelete} />
                }
            </button>
        );
    }
}
