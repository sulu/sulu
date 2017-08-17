// @flow
import React from 'react';
import actionStyles from './action.scss';

type Props = {
    children?: string,
    onClick: () => void,
    afterAction?: () => void,
};

export default class Action extends React.PureComponent<Props> {
    handleButtonClick = () => {
        this.props.onClick();
        if (this.props.afterAction) {
            this.props.afterAction();
        }
    };

    render() {
        return (
            <li>
                <button className={actionStyles.action} onClick={this.handleButtonClick}>{this.props.children}</button>
            </li>
        );
    }
}
