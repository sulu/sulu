// @flow
import React from 'react';
import actionStyles from './action.scss';

type Props<T> = {|
    children: string,
    onClick: (value: ?T) => void,
    afterAction?: () => void,
    value?: T,
|};

export default class Action<T> extends React.PureComponent<Props<T>> {
    handleButtonClick = () => {
        const {
            onClick,
            afterAction,
            value,
        } = this.props;

        onClick(value);

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
