// @flow
import React from 'react';
import crumbStyles from './crumb.scss';

type Props = {
    onClick?: (value?: string | number) => void,
    children: string,
    value?: string | number,
};

export default class Crumb extends React.PureComponent<Props> {
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
                onClick={this.handleClick}
                disabled={!onClick}
                className={crumbStyles.crumb}
            >
                {children}
            </button>
        );
    }
}
