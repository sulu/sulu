// @flow
import React from 'react';
import itemStyles from './item.scss';

type Props = {
    onClick?: (value?: string | number) => void,
    children: string,
    value?: string | number,
};

export default class Item extends React.PureComponent<Props> {
    handleClick = () => {
        const {
            value,
            onClick,
        } = this.props;

        if (onClick) {
            onClick(value);
        }
    };

    render() {
        const {
            onClick,
            children,
        } = this.props;

        return (
            <button
                className={itemStyles.item}
                disabled={!onClick}
                onClick={this.handleClick}
            >
                {children}
            </button>
        );
    }
}
