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
    name?: string,
    valid: boolean,
    value: ?string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
|};

@observer
export default class ColorPicker extends React.Component<Props> {
    static defaultProps = {
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
        onChange(value.hex);

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
            valid,
        } = this.props;

        const iconStyle = {
            color: this.isValidValue ? this.value : 'transparent',
        };

        return (
            <Fragment>
                <Input
                    inputRef={this.setRef}
                    onBlur={this.handleBlur}
                    onChange={this.handleInputChange}
                    onIconClick={this.handlePopoverOpen}
                    icon="su-square"
                    value={this.value}
                    valid={valid && !this.showError}
                    iconStyle={iconStyle}
                    iconClassName={colorPickerStyles.icon}
                />
                <Popover
                    open={this.popoverOpen}
                    anchorElement={this.popoverAnchorElement}
                    onClose={this.handlePopoverClose}
                    verticalOffset={-29}
                    horizontalOffset={35}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <div
                                style={popoverStyle}
                                ref={setPopoverElementRef}
                            >
                                <SketchPicker
                                    presetColors={[]}
                                    color={this.value}
                                    onChangeComplete={this.handleChange}
                                    disableAlpha={true}
                                />
                            </div>
                        )
                    }
                </Popover>
            </Fragment>
        );
    }
}
