// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import type {Button as ButtonProps} from './types';
import buttonStyles from './button.scss';

type Props = ButtonProps & {
    onToggle: (handle: string, style: string) => void,
    active?: boolean,
};

export default class Button extends React.Component<Props> {
    handleOnMouseDown = (event: SyntheticEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.onToggle(this.props.handle, this.props.style);
    };

    handleClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        event.preventDefault();
    };

    render() {
        const {
            icon,
            active,
        } = this.props;

        const buttonClass = classNames(
            buttonStyles.button,
            {
                [buttonStyles.active]: active,
            }
        );

        return (
            <button
                className={buttonClass}
                onMouseDown={this.handleOnMouseDown}
                onClick={this.handleClick}
            >
                <Icon name={icon} />
            </button>
        );
    }
}
