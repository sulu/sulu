// @flow
import React from 'react';
import actionStyles from './action.scss';

type Props = {
    afterAction?: () => void,
    children: string,
    onClick: () => void,
};

export default class Action extends React.PureComponent<Props> {
    handleButtonClick = () => {
        const {
            onClick,
            afterAction,
        } = this.props;

        onClick();

        if (afterAction) {
            afterAction();
        }
    };

    render() {
        return (
            <li>
                <button className={actionStyles.action} onClick={this.handleButtonClick}>
                    {this.props.children}
                </button>
            </li>
        );
    }
}
