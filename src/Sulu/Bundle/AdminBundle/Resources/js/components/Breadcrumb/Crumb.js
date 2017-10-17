// @flow
import React from 'react';
import crumbStyles from './crumb.scss';

type Props = {
    onClick?: () => void,
    children: string,
};

export default class Crumb extends React.PureComponent<Props> {
    render() {
        const {
            onClick,
            children,
        } = this.props;

        return (
            <button
                onClick={onClick}
                disabled={!onClick}
                className={crumbStyles.crumb}
            >
                {children}
            </button>
        );
    }
}
