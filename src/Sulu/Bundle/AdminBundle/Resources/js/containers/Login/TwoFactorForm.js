// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils/index';
import Button from '../../components/Button/index';
import Checkbox from '../../components/Checkbox/index';
import Input from '../../components/Input/index';
import Header from './Header';
import formStyles from './form.scss';
import type {ElementRef} from 'react';
import type {TwoFactorFormData} from './types';

type Props = {|
    error: boolean,
    loading: boolean,
    methods: Array<string>,
    onChangeForm: () => void,
    onSubmit: (data: TwoFactorFormData) => void,
|};

@observer
class TwoFactorForm extends React.Component<Props> {
    static defaultProps = {
        error: false,
        loading: false,
        methods: [],
    };

    @observable inputRef: ?ElementRef<*>;

    @observable authCode: ?string;

    @observable trustedDevice: boolean = false;

    @computed get submitButtonDisabled(): boolean {
        return !this.authCode;
    }

    @action setInputRef = (ref: ?ElementRef<*>) => {
        this.inputRef = ref;
    };

    componentDidMount() {
        if (this.inputRef) {
            this.inputRef.focus();
        }
    }

    @action handleAuthCodeChange = (authCode: ?string) => {
        this.authCode = authCode;
    };

    @action handleTrustedDeviceChange = (trustedDevice: boolean) => {
        this.trustedDevice = trustedDevice;
    };

    handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.authCode) {
            return;
        }

        const {onSubmit} = this.props;

        onSubmit({
            _auth_code: this.authCode,
            _trusted: this.trustedDevice,
        });
    };

    render() {
        const {error, methods} = this.props;

        const inputFieldClass = classNames(
            formStyles.inputField,
            {
                [formStyles.error]: error,
            }
        );

        return (
            <Fragment>
                <Header small={error}>
                    {
                        translate(
                            error
                                ? 'sulu_admin.two_factor_authentication_failed'
                                : 'sulu_admin.two_factor_authentication'
                        )
                    }
                </Header>

                <form className={formStyles.form} onSubmit={this.handleSubmit}>
                    <fieldset>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.two_factor_auth_code')}
                            </div>
                            <Input
                                autocomplete="one-time-code"
                                icon="su-lock"
                                inputRef={this.setInputRef}
                                onChange={this.handleAuthCodeChange}
                                valid={!error}
                                value={this.authCode}
                            />
                        </label>
                        {methods.includes('trusted_devices') &&
                            <Checkbox
                                checked={this.trustedDevice}
                                onChange={this.handleTrustedDeviceChange}
                                size="small"
                            >
                                {translate('sulu_admin.two_factor_trust_device')}
                            </Checkbox>
                        }
                        <div className={formStyles.buttons}>
                            <Button onClick={this.props.onChangeForm} skin="link">
                                {translate('sulu_admin.back_to_login')}
                            </Button>
                            <Button
                                disabled={this.submitButtonDisabled}
                                loading={this.props.loading}
                                skin="primary"
                                type="submit"
                            >
                                {translate('sulu_admin.verify')}
                            </Button>
                        </div>
                    </fieldset>
                </form>
            </Fragment>
        );
    }
}

export default TwoFactorForm;
