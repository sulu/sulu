// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import {SketchPicker} from 'react-color';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import Input from '../Input';
import Popover from '../Popover';
import colorPickerStyles from './colorPicker.scss';
import './colorPickerGlobal.scss';

type Props = {|
    id?: string,
    name?: string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
    placeholder?: string,
    valid: boolean,
    disabled: boolean,
    value: ?string,
|};

export default @observer class ColorPicker extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        valid: true,
    };

    @observable value: ?string;
    @observable showError: boolean = false;
    @observable popoverOpen: boolean = false;
    @observable popoverAnchorElement: ?ElementRef<*>;

    @action handlePopoverOpen = () => {
        this.popoverOpen = true;
    };

    @action handlePopoverClose = () => {
        this.popoverOpen = false;
    };

    @action setRef = (ref: ?ElementRef<'label'>) => {
        this.popoverAnchorElement = ref;
    };

    @action setValue(value: ?string) {
        this.value = value;
    }

    @action setShowError(showError: boolean) {
        this.showError = showError;
    }

    @computed get isValidValue(): boolean {
        if (!this.value) {
            return true;
        }

        return /(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test(this.value);
    }

    componentDidMount() {
        this.setValue(this.props.value);
    }

    componentDidUpdate() {
        if (this.value && !this.props.value) {
            return;
        }

        this.setValue(this.props.value);
    }

    handleBlur = () => {
        if (this.isValidValue) {
            this.setShowError(false);
        } else {
            this.props.onChange(undefined);
            this.setShowError(true);
        }

        const {onBlur} = this.props;

        if (onBlur) {
            onBlur();
        }
    };

    handleChange = (value: Object) => {
        const {onBlur, onChange} = this.props;

        this.setShowError(false);
        onChange(value && value instanceof Object && value.hasOwnProperty('hex') ? value.hex : undefined);

        if (onBlur) {
            onBlur();
        }
    };

    handleInputChange = (value: ?string) => {
        this.setValue(value);

        if (!this.isValidValue) {
            this.props.onChange(undefined);

            return;
        }

        this.setShowError(false);
        this.props.onChange(this.value);
    };

    render() {
        const {
            disabled,
            id,
            name,
            placeholder,
            valid,
        } = this.props;

        const iconStyle = {
            color: this.isValidValue ? this.value : 'transparent',
        };

        return (
            <Fragment>
                <Input
                    disabled={disabled}
                    icon="su-square"
                    iconClassName={colorPickerStyles.icon}
                    iconStyle={iconStyle}
                    id={id}
                    labelRef={this.setRef}
                    name={name}
                    onBlur={this.handleBlur}
                    onChange={this.handleInputChange}
                    onIconClick={!disabled ? this.handlePopoverOpen : undefined}
                    placeholder={placeholder}
                    valid={valid && !this.showError}
                    value={this.value}
                />
                <Popover
                    anchorElement={this.popoverAnchorElement}
                    horizontalOffset={35}
                    onClose={this.handlePopoverClose}
                    open={this.popoverOpen}
                    verticalOffset={-30}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <div
                                ref={setPopoverElementRef}
                                style={popoverStyle}
                            >
                                <SketchPicker
                                    color={this.value ? this.value : undefined}
                                    disableAlpha={true}
                                    onChangeComplete={this.handleChange}
                                    presetColors={[]}
                                />
                            </div>
                        )
                    }
                </Popover>
            </Fragment>
        );
    }
}
