// @flow
import React from 'react';
import {Icon} from 'sulu-admin-bundle/components';
import buttonStyles from './button.scss';

type Props = {
    disabled: boolean,
    icon: string,
    onClick: () => void,
};

class Button extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
    };

    handleClick = (event: SyntheticEvent<HTMLButtonElement>) => {
        const {onClick} = this.props;

        event.preventDefault();
        onClick();
    };

    render() {
        const {disabled, icon} = this.props;

        return (
            <button
                className={buttonStyles.button}
                disabled={disabled}
                onClick={this.handleClick}
                type="button"
            >
                <Icon name={icon} />
            </button>
        );
    }
}

export default Button;
