// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from '../../utils/index';
import Button from '../../components/Button/index';
import Input from '../../components/Input/index';
import Header from './Header';
import formStyles from './form.scss';
import type {ElementRef} from 'react';
import type {TwoFactorFormData} from './types';

type Props = {|
    loading: boolean,
    onChangeForm: () => void,
    onSubmit: (data: TwoFactorFormData) => void,
|};

@observer
class TwoFactorForm extends React.Component<Props> {
    static defaultProps = {
        loading: false,
    };

    @observable inputRef: ?ElementRef<*>;

    @observable authCode: ?string;

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

    handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.authCode) {
            return;
        }

        const {onSubmit} = this.props;

        onSubmit({authCode: this.authCode});
    };

    render() {
        return (
            <Fragment>
                <Header>
                    {translate('sulu_admin.two_factor_authentication')}
                </Header>

                <form className={formStyles.form} onSubmit={this.handleSubmit}>
                    <fieldset>
                        <label className={formStyles.inputField}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.two_factor_auth_code')}
                            </div>
                            <Input
                                autocomplete="one-time-code"
                                icon="su-lock"
                                inputRef={this.setInputRef}
                                onChange={this.handleAuthCodeChange}
                                value={this.authCode}
                            />
                        </label>
                        <div className={formStyles.buttons}>
                            <Button onClick={this.props.onChangeForm} skin="link">
                                {translate('sulu_admin.to_login')}
                            </Button>
                            <Button
                                disabled={this.submitButtonDisabled}
                                loading={this.props.loading}
                                skin="primary"
                                type="submit"
                            >
                                {translate('sulu_admin.login')}
                            </Button>
                        </div>
                    </fieldset>
                </form>
            </Fragment>
        );
    }
}

export default TwoFactorForm;
