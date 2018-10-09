// @flow
import React from 'react';
import actionStyles from './action.scss';

type Props = {
    children: string,
    onClick: () => void,
};

export default class Action extends React.PureComponent<Props> {
    render() {
        const {
            onClick,
        } = this.props;

        return (
            <button className={actionStyles.action} onClick={onClick}>
                {this.props.children}
            </button>
        );
    }
}
