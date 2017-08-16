// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classnames from 'classnames';
import {afterElementsRendered} from '../../services/DOM';
import Icon from '../Icon';
import Checkbox from '../Checkbox';
import type {OptionSelectedVisualization} from './types';
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
        const classNames = classnames({
            [optionStyles.option]: true,
            [optionStyles.selected]: this.props.selected,
            [optionStyles.hasCheckbox]: this.props.selectedVisualization === 'checkbox',
        });

        return (
            <li ref={this.setItem}>
                <button
                    className={classNames}
                    ref={this.setButton}
                    onClick={this.handleButtonClick}
                    disabled={this.props.disabled}>
                    {this.renderSelectedVisualization()}
                    {this.props.children}
                </button>
            </li>
        );
    }

    renderSelectedVisualization() {
        if (this.props.selectedVisualization === 'icon') {
            return this.props.selected ? <Icon className={optionStyles.icon} name={SELECTED_ICON} /> : null;
        } else {
            return (
                <Checkbox
                    onChange={this.handleButtonClick}
                    className={optionStyles.checkbox}
                    checked={this.props.selected} />
            );
        }
    }
}
