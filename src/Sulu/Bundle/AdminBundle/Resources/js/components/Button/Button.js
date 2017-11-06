// @flow
import React from 'react';
import classNames from 'classnames';
import buttonStyles from './button.scss';

type Props = {
    children: string,
    skin: 'primary' | 'secondary',
    onClick: () => void,
};

export default class Button extends React.PureComponent<Props> {
    handleClick = () => {
        this.props.onClick();
    };

    render() {
        const {
            children,
            skin,
        } = this.props;

        const buttonClass = classNames(buttonStyles.button, buttonStyles[skin]);

        return (
            <button className={buttonClass} onClick={this.handleClick}>
                {children}
            </button>
        );
    }
}
