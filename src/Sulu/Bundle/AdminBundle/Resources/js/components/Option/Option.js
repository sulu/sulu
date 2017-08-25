// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import {afterElementsRendered} from '../../services/DOM/index';
import Icon from '../Icon/index';
import Checkbox from '../Checkbox/index';
import type {OptionSelectedVisualization} from '../GenericSelect/types';
import optionStyles from './option.scss';

type Props = {
    selected: boolean,
    disabled: boolean,
    focus: boolean,
    value: string,
    children: string,
    onClick?: (value: string) => void,
    selectedVisualization: OptionSelectedVisualization,
};

const SELECTED_ICON = 'check';

export default class Option extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
        selected: false,
        focus: false,
        selectedVisualization: 'icon',
        value: '',
    };

    item: ElementRef<'li'>;

    /** @public **/
    getOffsetTop() {
        return this.item.offsetTop;
    }

    handleButtonClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.value);
        }
    };

    setItem = (item: ElementRef<'li'>) => this.item = item;
    setButton = (button: ElementRef<'button'>) => {
        if (!button) {
            return;
        }
        if (this.props.focus) {
            afterElementsRendered(() => {
                button.focus();
            });
        }
    };

    render() {
        const {selected, selectedVisualization, disabled, children} = this.props;
        const optionClass = classNames({
            [optionStyles.option]: true,
            [optionStyles[selectedVisualization]]: true,
            [optionStyles.selected]: selected,
        });

        return (
            <li ref={this.setItem}>
                <button
                    className={optionClass}
                    ref={this.setButton}
                    onClick={this.handleButtonClick}
                    disabled={disabled}>
                    {this.renderSelectedVisualization()}
                    {children}
                </button>
            </li>
        );
    }

    renderSelectedVisualization() {
        if (this.props.selectedVisualization === 'icon') {
            return this.props.selected ? <Icon className={optionStyles.icon} name={SELECTED_ICON} /> : null;
        }

        return (
            <Checkbox
                onChange={this.handleButtonClick}
                className={optionStyles.input}
                checked={this.props.selected} />
        );
    }
}
