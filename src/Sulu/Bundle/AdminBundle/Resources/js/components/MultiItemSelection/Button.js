// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import buttonStyles from './button.scss';
import type {Button as ButtonConfig} from './types';

type Props = {|
    ...ButtonConfig,
    location: 'left' | 'right',
|};

export default class Button extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
    };

    render() {
        const {
            disabled,
            icon,
            location,
            onClick,
        } = this.props;

        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[location]
        );

        return (
            <button
                className={buttonClass}
                disabled={disabled}
                onClick={onClick}
                type="button"
            >
                <Icon name={icon} />
            </button>
        );
    }
}
