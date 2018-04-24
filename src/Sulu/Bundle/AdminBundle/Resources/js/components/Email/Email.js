// @flow
import React from 'react';
import Isemail from 'isemail';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
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

    componentWillReceiveProps(nextProps: Props) {
        if (this.value && !nextProps.value) {
            return;
        }

        this.setValue(nextProps.value);
    }

    handleIconClick = () => {
        const {value} = this.props;
        if (!value) {
            return;
        }

        window.location.href = 'mailto:' + value;
    };

    handleBlur = () => {
        if (!this.value || !Isemail.validate(this.value)) {
            this.props.onChange(undefined);
            this.setShowError(true);

            return;
        }

        this.setShowError(false);
    };

    handleChange = (value: ?string) => {
        this.setValue(value);

        if (this.value && Isemail.validate(this.value)) {
            this.setShowError(false);
            this.props.onChange(this.value);

            return;
        }

        this.props.onChange(undefined);
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
