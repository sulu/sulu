// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import {afterElementsRendered} from '../../services/DOM';
import Icon from '../Icon';
import Checkbox from '../Checkbox';
import type {OptionSelectedVisualization} from './types';
import optionStyles from './option.scss';

type Props = {
    anchorWidth: number,
    selected: boolean,
    disabled: boolean,
    focus: boolean,
    value: string | number,
    children: string,
    onClick?: (value: string | number) => void,
    optionRef?: (optionNode: ElementRef<'li'>, selected: boolean) => void,
    selectedVisualization: OptionSelectedVisualization,
};

const ANCHOR_WIDTH_DIFFERENCE = 10;

export default class Option extends React.PureComponent<Props> {
    static defaultProps = {
        anchorWidth: 0,
        disabled: false,
        selected: false,
        focus: false,
        selectedVisualization: 'icon',
        value: '',
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
        if ('icon' === this.props.selectedVisualization) {
            return this.props.selected ? <Icon className={optionStyles.icon} name={'su-check'} /> : null;
        }

        return (
            <Checkbox
                onChange={this.handleButtonClick}
                className={optionStyles.input}
                checked={this.props.selected}
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
                    style={{minWidth: anchorWidth + ANCHOR_WIDTH_DIFFERENCE}}
                    ref={this.setButtonRef}
                    onClick={this.handleButtonClick}
                    disabled={disabled}
                >
                    {this.renderSelectedVisualization()}
                    {children}
                </button>
            </li>
        );
    }
}
