// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import {afterElementsRendered} from '../../services/DOM';
import Icon from '../Icon';
import Checkbox from '../Checkbox';
import type {OptionSelectedVisualization} from './types';
import optionStyles from './option.scss';

type Props<T> = {|
    anchorWidth: number,
    selected: boolean,
    disabled: boolean,
    focus: boolean,
    value: T,
    children: string,
    onClick?: (value: T) => void,
    optionRef?: (optionNode: ElementRef<'li'>, selected: boolean) => void,
    selectedVisualization: OptionSelectedVisualization,
|};

const ANCHOR_WIDTH_DIFFERENCE = 10;

export default class Option<T> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        anchorWidth: 0,
        disabled: false,
        focus: false,
        selected: false,
        selectedVisualization: 'icon',
    };

    item: ElementRef<'li'>;

    handleButtonClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.value);
        }
    };

    setItemRef = (item: ?ElementRef<'li'>) => {
        const {
            selected,
            optionRef,
        } = this.props;

        if (optionRef && item) {
            optionRef(item, selected);
        }
    };

    setButtonRef = (button: ?ElementRef<'button'>) => {
        if (this.props.focus) {
            afterElementsRendered(() => {
                if (!button) {
                    return;
                }

                button.focus();
            });
        }
    };

    renderSelectedVisualization() {
        if (this.props.selectedVisualization === 'icon') {
            return this.props.selected ? <Icon className={optionStyles.icon} name="su-check" /> : null;
        }

        return (
            <Checkbox
                checked={this.props.selected}
                className={optionStyles.input}
                onChange={this.handleButtonClick}
            />
        );
    }

    render() {
        const {
            anchorWidth,
            selected,
            children,
            disabled,
            selectedVisualization,
        } = this.props;
        const optionClass = classNames(
            optionStyles.option,
            optionStyles[selectedVisualization],
            {
                [optionStyles.selected]: selected,
            }
        );

        return (
            <li ref={this.setItemRef}>
                <button
                    className={optionClass}
                    disabled={disabled}
                    onClick={this.handleButtonClick}
                    ref={this.setButtonRef}
                    style={{minWidth: anchorWidth + ANCHOR_WIDTH_DIFFERENCE}}
                >
                    {this.renderSelectedVisualization()}
                    {children}
                </button>
            </li>
        );
    }
}
