// @flow
import React from 'react';
import buttonStyles from './button.scss';

type Props = {
    children: string,
    skin: 'primary' | 'secondary' | 'link',
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

        return (
            <button className={buttonStyles[skin]} onClick={this.handleClick}>
                {children}
            </button>
        );
    }
}
