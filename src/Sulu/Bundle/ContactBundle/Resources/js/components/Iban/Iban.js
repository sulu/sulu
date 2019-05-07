// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import iban from 'iban';
import {Input} from 'sulu-admin-bundle/components';

type Props = {|
    id?: string,
    name?: string,
    placeholder?: string,
    valid: boolean,
    disabled: boolean,
    value: ?string,
    onBlur?: () => void,
    onChange: (value: ?string) => void,
|};

export default @observer class Iban extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
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

        return iban.isValid(this.value);
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
            id,
            valid,
            disabled,
            name,
            placeholder,
        } = this.props;

        return (
            <Input
                disabled={disabled}
                icon="su-credit-card"
                id={id}
                name={name}
                onBlur={this.handleBlur}
                onChange={this.handleChange}
                placeholder={placeholder}
                type="text"
                valid={valid && !this.showError}
                value={this.value}
            />
        );
    }
}
