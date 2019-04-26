// @flow
import React from 'react';
import actionStyles from './action.scss';

type Props<T> = {
    children: string,
    disabled: boolean,
    onAfterAction?: () => void,
    onClick: (value: ?T) => void,
    value?: T,
};

export default class Action<T> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        disabled: false,
    };

    handleButtonClick = () => {
        const {
            onClick,
            onAfterAction,
            value,
        } = this.props;

        onClick(value);

        if (onAfterAction) {
            onAfterAction();
        }
    };

    render() {
        const {disabled} = this.props;

        return (
            <button
                className={actionStyles.action}
                disabled={disabled}
                onClick={this.handleButtonClick}
            >
                {this.props.children}
            </button>
        );
    }
}
