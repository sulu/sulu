// @flow
import React from 'react';
import Icon from 'sulu-admin-bundle/components/Icon';
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

    render() {
        const {disabled, onClick, icon} = this.props;

        return (
            <button
                className={buttonStyles.button}
                disabled={disabled}
                onClick={onClick}
                type="button"
            >
                <Icon name={icon} />
            </button>
        );
    }
}

export default Button;
