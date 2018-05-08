// @flow
import React from 'react';
import Isemail from 'isemail';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import Input from '../Input';

type Props = {|
    name?: string,
    placeholder?: string,
    valid: boolean,
    value: ?string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
|};

@observer
export default class Email extends React.Component<Props> {
    static defaultProps = {
        valid: true,
    };

    @observable value: ?string;
    @observable showError: boolean = false;

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

        return Isemail.validate(this.value);
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

    handleIconClick = () => {
        const {value} = this.props;
        if (!value) {
            return;
        }

        window.location.assign('mailto:' + value);
    };

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

    handleChange = (value: ?string) => {
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
            name,
            placeholder,
            value,
        } = this.props;

        return (
            <Input
                icon="su-envalope"
                onChange={this.handleChange}
                value={this.value}
                type="email"
                valid={valid && !this.showError}
                name={name}
                placeholder={placeholder}
                onBlur={this.handleBlur}
                onIconClick={(value && value.length > 1) ? this.handleIconClick : undefined}
            />
        );
    }
}
