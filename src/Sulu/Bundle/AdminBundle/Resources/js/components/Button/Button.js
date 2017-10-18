// @flow
import React from 'react';
import classNames from 'classnames';
import buttonStyles from './button.scss';

type Props = {
    children: string,
    type: 'confirm' | 'cancel',
    onClick: () => void,
};

export default class Button extends React.PureComponent<Props> {
    handleClick = () => {
        this.props.onClick();
    };

    render() {
        const {
            children,
            type,
        } = this.props;

        const buttonClass = classNames(
            buttonStyles.button,
            {
                [buttonStyles[type]]: type,
            }
        );

        return (
            <button className={buttonClass} onClick={this.handleClick}>
                {children}
            </button>
        );
    }
}
