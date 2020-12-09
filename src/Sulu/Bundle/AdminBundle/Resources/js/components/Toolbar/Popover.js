// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import PopoverComponent from '../Popover';
import type {Popover as PopoverProps} from './types';
import Button from './Button';
import popoverStyles from './popover.scss';

@observer
class Popover extends React.Component<PopoverProps> {
    @observable open: boolean = false;

    static defaultProps = {
        showText: true,
    };

    @observable buttonRef: ?ElementRef<'button'>;

    @action setButtonRef = (ref: ?ElementRef<'button'>) => {
        if (ref) {
            this.buttonRef = ref;
        }
    };

    @action close = () => {
        this.open = false;
    };

    @action toggle = () => {
        this.open = !this.open;
    };

    componentDidUpdate() {
        const {disabled} = this.props;

        if (disabled) {
            this.close();
        }
    }

    handleButtonClick = () => {
        this.toggle();
    };

    handlePopoverClose = () => {
        this.close();
    };

    render() {
        const {
            children,
            className,
            icon,
            size,
            skin,
            label,
            disabled,
            loading,
            showText,
        } = this.props;
        const popoverClass = classNames(
            className,
            popoverStyles.popover,
            {
                [popoverStyles[size]]: size,
            }
        );

        return (
            <div className={popoverClass}>
                <Button
                    active={this.open}
                    buttonRef={this.setButtonRef}
                    disabled={disabled}
                    hasOptions={true}
                    icon={icon}
                    label={showText ? label : undefined}
                    loading={loading}
                    onClick={this.handleButtonClick}
                    size={size}
                    skin={skin}
                />
                <PopoverComponent
                    anchorElement={this.buttonRef}
                    onClose={this.handlePopoverClose}
                    open={this.open}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <div ref={setPopoverElementRef} style={popoverStyle}>
                                {children(this.close)}
                            </div>
                        )
                    }
                </PopoverComponent>
            </div>
        );
    }
}

export default Popover;
