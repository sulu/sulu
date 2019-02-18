// @flow
import React from 'react';
import actionStyles from './action.scss';

type Props = {
    children: string,
    disabled?: boolean,
    onClick: () => void,
};

export default class Action extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
    };
    handleButtonClick = () => {
        const {
            onClick,
            onAfterAction,
        } = this.props;

        onClick();

        if (onAfterAction) {
            onAfterAction();
        }
    };

    render() {
        const {disabled} = this.props;
        return (
            <button
                className={disabled ? actionStyles.actionDisabled : actionStyles.action}
                disabled={disabled}
                onClick={this.handleButtonClick}
            >
                {this.props.children}
            </button>
        );
    }
}
