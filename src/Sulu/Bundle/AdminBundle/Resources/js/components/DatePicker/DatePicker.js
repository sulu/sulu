// @flow
import React from 'react';
import ReactDOM from 'react-dom';
import type {ElementRef} from 'react';
import ReactDatetime from 'react-datetime';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import 'react-datetime/css/react-datetime.css';
import type Moment from 'moment';
import moment from 'moment';
import Input from '../Input';
import Popover from '../Popover';
import './datePicker.scss';

type Props = {|
    id?: string,
    /** Placeholder can be overwritten */
    placeholder?: string,
    /** Listen for changes of this component */
    onChange: (value: ?Date) => void,
    /** Configure the datepicker to your needs, for more information have a look in the README.md */
    options: {
        [any]: any,
        dateFormat?: ?string | boolean,
        timeFormat?: ?string | boolean,
    },
    valid: boolean,
    disabled: boolean,
    value: ?Date,
|};

@observer
export default class DatePicker extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        options: {},
        valid: true,
    };

    inputChanged: boolean = false;

    @observable open: boolean = false;
    @observable showError: boolean = false;
    @observable value: ?string | ?Date | ?Moment = null;
    @observable inputRef: ?ElementRef<*>;

    @action setOpen(open: boolean) {
        this.open = open;
    }

    @action setValue(value: ?string | ?Date | ?Moment) {
        this.value = value;
    }

    @action setShowError(showError: boolean) {
        this.showError = showError;
    }

    @action setInputRef = (ref: ?ElementRef<*>) => {
        this.inputRef = ref;
    };

    constructor(props: Props) {
        super(props);

        this.setValue(this.props.value);
    }

    componentWillReceiveProps(nextProps: Props) {
        if (this.value && !nextProps.value) {
            return;
        }

        this.setValue(nextProps.value);
    }

    handleChange = (date: ?Date) => {
        this.inputChanged = false;
        this.props.onChange(date);

        this.setShowError(!!this.value && !date);
    };

    handleDatepickerChange = (date: string | Moment) => {
        if (!date) {
            this.setValue(undefined);
            this.handleChange(undefined);

            return;
        }

        if (typeof date === 'string') {
            this.setValue(date);

            return;
        }

        if (!date.isValid()) {
            this.handleChange(undefined);

            return;
        }

        this.handleChange(date.toDate());
    };

    handleInputBlur = () => {
        if (this.inputChanged && typeof this.value === 'string') {
            const newMoment = moment(this.value, this.getFormat());

            this.handleChange(newMoment.isValid() ? newMoment.toDate() : undefined);
        }
    };

    handleOpenOverlay = () => {
        this.setOpen(true);
    };

    handleCloseOverlay = () => {
        this.setOpen(false);
    };

    getInputChange = (props: Object) => {
        return (value: ?string, event: SyntheticEvent<HTMLInputElement>) => {
            this.inputChanged = true;
            props.onChange(event);
        };
    };

    getDateFormat = (): string => {
        let dateFormat = this.props.options.dateFormat;

        if ((!dateFormat && dateFormat !== false) || dateFormat === true || (!dateFormat && !this.getTimeFormat())) {
            dateFormat = moment.localeData().longDateFormat('L');
        }

        return dateFormat || '';
    };

    getTimeFormat = (): string => {
        let timeFormat = this.props.options.timeFormat;

        if (timeFormat === true) {
            timeFormat = moment.localeData().longDateFormat('LT');
        }

        return timeFormat || '';
    };

    getFormat = (): string => {
        return [
            this.getDateFormat(),
            this.getTimeFormat(),
        ].filter((format) => !!format).join(' ');
    };

    renderInput = (props: Object) => {
        const handleInputChange = this.getInputChange(props);

        if (!this.inputRef) {
            return null;
        }

        return ReactDOM.createPortal(
            <Input
                {...props}
                id={this.props.id}
                onBlur={this.handleInputBlur}
                onChange={handleInputChange}
                onIconClick={!props.disabled ? this.handleOpenOverlay : undefined}
            />,
            this.inputRef
        );
    };

    render() {
        const {disabled, options, placeholder, valid} = this.props;

        const fieldOptions = {
            closeOnSelect: true,
            ...options,
            dateFormat: this.getDateFormat() || false,
            timeFormat: this.getTimeFormat() || false,
        };

        const inputProps = {
            placeholder: placeholder ? placeholder : this.getFormat(),
            valid: valid && !this.showError,
            disabled: disabled,
            icon: fieldOptions.dateFormat ? 'su-calendar' : 'su-clock',
        };

        return (
            <div>
                <div ref={this.setInputRef} />
                <Popover
                    anchorElement={this.inputRef}
                    backdrop={this.open}
                    horizontalOffset={34}
                    onClose={this.handleCloseOverlay}
                    open={true}
                    verticalOffset={-31}
                >
                    {
                        (setPopoverRef, styles) => (
                            <div ref={setPopoverRef} style={styles}>
                                <ReactDatetime
                                    {...fieldOptions}
                                    inputProps={inputProps}
                                    onBlur={this.handleCloseOverlay}
                                    onChange={this.handleDatepickerChange}
                                    open={this.open}
                                    renderInput={this.renderInput}
                                    value={this.value}
                                />
                            </div>
                        )
                    }
                </Popover>
            </div>
        );
    }
}
