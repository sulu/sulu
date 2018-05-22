// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import buttonStyles from './button.scss';

type Props = {
    icon: string,
    location: 'left' | 'right',
    onClick: () => void,
};

export default class Button extends React.PureComponent<Props> {
    handleClick = () => {
        this.props.onClick();
    };

    render() {
        const {
            icon,
            location,
        } = this.props;
        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[location]
        );

        return (
            <button
                className={buttonClass}
                onClick={this.handleClick}
                type="button"
            >
                <Icon name={icon} />
            </button>
        );
    }
}
