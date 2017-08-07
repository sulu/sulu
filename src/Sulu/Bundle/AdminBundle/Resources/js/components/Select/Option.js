// @flow
import Icon from '../Icon';
import React from 'react';
import type {SelectData} from './types';
import classnames from 'classnames';
import itemStyles from './selectItem.scss';

export default class Option extends React.PureComponent {
    props: {
        selected: boolean,
        disabled: boolean,
        focus: boolean,
        value?: string,
        children?: string,
        onClick?: (SelectData) => void,
    };

    static defaultProps = {
        disabled: false,
        selected: false,
        focus: false,
    };

    element: HTMLElement;
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
        return this.element.offsetTop;
    }

    handleButtonClick = () => {
        if (this.props.onClick) {
            this.props.onClick({
                value: this.props.value || '',
                label: this.props.children || '',
            });
        }
    };

    setElement = (e: HTMLElement) => this.element = e;
    setButton = (b: HTMLButtonElement) => this.button = b;

    render() {
        const classNames = classnames({
            [itemStyles.selectItem]: true,
            [itemStyles.disabled]: this.props.disabled,
            [itemStyles.selected]: this.props.selected,
        });

        return (
            <li ref={this.setElement} className={classNames}>
                <button
                    ref={this.setButton}
                    onClick={this.handleButtonClick}
                    disabled={this.props.disabled}>
                    {this.props.selected ? <Icon className={itemStyles.icon} name="check" /> : ''}
                    {this.props.children}
                </button>
            </li>
        );
    }
}
