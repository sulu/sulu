// @flow
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import buttonStyles from './button.scss';

type Props = {
    children: Node,
    icon?: string,
    skin: 'primary' | 'secondary' | 'link' | 'underlined',
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
            icon,
        } = this.props;

        return (
            <button className={buttonStyles[skin]} onClick={this.handleClick}>
                {!!icon &&
                    <Icon className={buttonStyles.icon} name={icon} />
                }
                <span className={buttonStyles.text}>{children}</span>
            </button>
        );
    }
}
