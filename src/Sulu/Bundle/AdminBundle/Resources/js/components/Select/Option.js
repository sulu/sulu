// @flow
import React from 'react';
import classnames from 'classnames';
import Icon from '../Icon';
import optionStyles from './option.scss';

const SELECTED_ICON = 'check';

export default class Option extends React.PureComponent {
    props: {
        selected: boolean,
        disabled: boolean,
        focus: boolean,
        value: string,
        children?: string,
        onClick?: (s: string) => void,
    };

    static defaultProps = {
        disabled: false,
        selected: false,
        focus: false,
        value: '',
    };

    item: HTMLElement;
    button: HTMLButtonElement;

    componentDidMount() {
        if (this.props.focus) {
            window.requestAnimationFrame(() => {
                this.button.focus();
            });
        }
    }

    /** @public **/
    getOffsetTop() {
        return this.item.offsetTop;
    }

    handleButtonClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.value);
        }
    };

    setItem = (i: HTMLElement) => this.item = i;
    setButton = (b: HTMLButtonElement) => this.button = b;

    render() {
        const classNames = classnames({
            [optionStyles.option]: true,
            [optionStyles.disabled]: this.props.disabled,
            [optionStyles.selected]: this.props.selected,
        });

        return (
            <li ref={this.setItem} className={classNames}>
                <button
                    ref={this.setButton}
                    onClick={this.handleButtonClick}
                    disabled={this.props.disabled}>
                    {this.props.selected ? <Icon className={optionStyles.icon} name={SELECTED_ICON} /> : ''}
                    {this.props.children}
                </button>
            </li>
        );
    }
}
