// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../../components/Icon';
import buttonStyles from './button.scss';

type Props = {
    onToggle: (style: string) => void,
    icon: string,
    type: string,
    active?: boolean,
};

export default class Button extends React.Component<Props> {
    handleOnMouseDown = (event: SyntheticEvent<HTMLButtonElement>) => {
        event.preventDefault();
        this.props.onToggle(this.props.type);
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
