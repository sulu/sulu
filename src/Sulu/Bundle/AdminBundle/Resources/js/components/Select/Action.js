// @flow
import React from 'react';
import actionStyles from './action.scss';
import type {ElementRef} from 'react';

type Props<T> = {|
    afterAction?: () => void,
    buttonRef?: (buttonRef: ?ElementRef<'button'>) => void,
    children: string,
    onClick: (value: ?T) => void,
    requestFocus?: () => void,
    value?: T,
|};

export default class Action<T> extends React.PureComponent<Props<T>> {
    triggerButton = () => {
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

    handleButtonClick = () => {
        this.triggerButton();
    };

    handleButtonKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Enter' || event.key === 'Space') {
            event.preventDefault();
            event.stopPropagation();
            this.triggerButton();
        }
    };

    setButtonRef = (ref: ?ElementRef<'button'>) => {
        const {buttonRef} = this.props;

        if (buttonRef) {
            buttonRef(ref);
        }
    };

    handleMouseMove = () => {
        if (this.props.requestFocus) {
            this.props.requestFocus();
        }
    };

    render() {
        return (
            <li onMouseMove={this.handleMouseMove}>
                <button
                    className={actionStyles.action}
                    onClick={this.handleButtonClick}
                    onKeyDown={this.handleButtonKeyDown}
                    ref={this.setButtonRef}
                >
                    {this.props.children}
                </button>
            </li>
        );
    }
}
