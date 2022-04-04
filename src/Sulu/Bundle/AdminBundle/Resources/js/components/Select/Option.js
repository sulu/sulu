// @flow
import React from 'react';
import classNames from 'classnames';
import Icon from '../Icon';
import Checkbox from '../Checkbox';
import optionStyles from './option.scss';
import type {OptionSelectedVisualization} from './types';
import type {ElementRef} from 'react';

type Props<T> = {|
    anchorWidth: number,
    buttonRef?: (buttonRef: ?ElementRef<'button'>) => void,
    children: string,
    disabled: boolean,
    onClick?: (value: T) => void,
    optionRef?: (optionNode: ElementRef<'li'>, selected: boolean) => void,
    requestFocus?: () => void,
    selected: boolean,
    selectedVisualization: OptionSelectedVisualization,
    value: T,
|};

const ANCHOR_WIDTH_DIFFERENCE = 10;

export default class Option<T> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        anchorWidth: 0,
        disabled: false,
        selected: false,
        selectedVisualization: 'icon',
    };

    triggerButton = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.value);
        }
    };

    handleButtonClick = () => {
        this.triggerButton();
    };

    handleButtonKeyDown = (event: KeyboardEvent) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            event.stopPropagation();
            this.triggerButton();
        }
    };

    setItemRef = (ref: ?ElementRef<'li'>) => {
        const {
            optionRef,
            selected,
        } = this.props;

        if (optionRef && ref) {
            optionRef(ref, selected);
        }
    };

    setButtonRef = (ref: ?ElementRef<'button'>) => {
        const {buttonRef} = this.props;

        if (buttonRef) {
            buttonRef(ref);
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
                tabIndex={-1}
            />
        );
    }

    handleMouseMove = () => {
        if (this.props.requestFocus) {
            this.props.requestFocus();
        }
    };

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
            <li onMouseMove={this.handleMouseMove} ref={this.setItemRef}>
                <button
                    className={optionClass}
                    disabled={disabled}
                    onClick={this.handleButtonClick}
                    onKeyDown={this.handleButtonKeyDown}
                    ref={this.setButtonRef}
                    style={{minWidth: anchorWidth + ANCHOR_WIDTH_DIFFERENCE}}
                    type="button"
                >
                    {this.renderSelectedVisualization()}
                    {children}
                </button>
            </li>
        );
    }
}
