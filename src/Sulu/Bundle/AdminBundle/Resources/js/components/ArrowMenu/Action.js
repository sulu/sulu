// @flow
import React from 'react';
import Icon from '../Icon';
import actionStyles from './action.scss';

type Props<T> = {|
    children: string,
    disabled: boolean,
    icon?: string,
    onAfterAction?: () => void,
    onClick: (value: ?T) => void,
    value?: T,
|};

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
        const {disabled, icon} = this.props;

        return (
            <button
                className={actionStyles.action}
                disabled={disabled}
                onClick={this.handleButtonClick}
            >
                {icon && <Icon className={actionStyles.icon} name={icon} />}
                {this.props.children}
            </button>
        );
    }
}
