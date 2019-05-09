// @flow
import React from 'react';
import itemStyles from './item.scss';

type Props = {
    children: string,
    onClick?: (value?: string | number) => void,
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
