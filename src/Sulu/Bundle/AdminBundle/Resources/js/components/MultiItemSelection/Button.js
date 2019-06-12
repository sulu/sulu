// @flow
import React from 'react';
import classNames from 'classnames';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import ArrowMenu from '../ArrowMenu';
import Icon from '../Icon';
import buttonStyles from './button.scss';
import type {Button as ButtonConfig} from './types';

type Props<T> = {|
    ...ButtonConfig<T>,
    location: 'left' | 'right',
|};

@observer
export default class Button<T: string | number> extends React.Component<Props<T>> {
    @observable open: boolean = false;

    static defaultProps = {
        disabled: false,
    };

    handleOptionClick: (option: ?T) => void = (option: ?T) => {
        const {onClick} = this.props;

        onClick(option);
    };

    @action handleClick = () => {
        const {onClick, options} = this.props;

        if (options) {
            this.open = true;
            return;
        }

        onClick();
    };

    @action handleClose = () => {
        this.open = false;
    };

    render() {
        const {
            disabled,
            icon,
            label,
            location,
            options,
        } = this.props;

        const buttonClass = classNames(
            buttonStyles.button,
            buttonStyles[location],
            {
                [buttonStyles.hasLabel]: label,
                [buttonStyles.hasOptions]: options,
            }
        );

        const button = (
            <button
                className={buttonClass}
                disabled={disabled}
                onClick={this.handleClick}
                type="button"
            >
                {icon && <Icon className={buttonStyles.icon} name={icon} />}
                {label && <span className={buttonStyles.label}>{label}</span>}
                {options && <Icon name="su-angle-down" />}
            </button>
        );

        if (!options) {
            return button;
        }

        return (
            <ArrowMenu anchorElement={button} onClose={this.handleClose} open={this.open}>
                <ArrowMenu.Section>
                    {options.map((option) => (
                        <ArrowMenu.Action
                            icon={option.icon}
                            key={option.value}
                            onClick={this.handleOptionClick}
                            value={option.value}
                        >
                            {option.label}
                        </ArrowMenu.Action>
                    ))}
                </ArrowMenu.Section>
            </ArrowMenu>
        );
    }
}
