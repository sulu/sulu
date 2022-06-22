// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {Config} from '../../services';
import {translate} from '../../utils/index';
import Button from '../../components/Button/index';
import Input from '../../components/Input/index';
import fieldStyles from '../../components/Form/field.scss';
import {userStore} from '../../stores';
import formStyles from './form.scss';
import Header from './Header';
import type {ElementRef} from 'react';
import type {ResetPasswordFormData} from './types';

type Props = {|
    loading: boolean,
    onChangeForm: () => void,
    onSubmit: (data: ResetPasswordFormData) => void,
|};

@observer
class ResetPasswordForm extends React.Component<Props> {
    static defaultProps = {
        loading: false,
    };

    @observable inputRef: ?ElementRef<*>;

    @observable errorMessage: ?string = null;

    @observable password1: ?string;
    @observable password2: ?string;

    @computed get submitButtonDisabled(): boolean {
        return !(this.password1 && this.password2);
    }

    @action setInputRef = (ref: ?ElementRef<*>) => {
        this.inputRef = ref;
    };

    componentDidMount() {
        if (this.inputRef) {
            this.inputRef.focus();
        }
    }

    @action handlePassword1Change = (password1: ?string) => {
        this.password1 = password1;

        this.errorMessage = null;
    };

    @action handlePassword2Change = (password2: ?string) => {
        this.password2 = password2;

        this.errorMessage = null;
    };

    @action handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.password1 || !this.password2 || this.password1 !== this.password2) {
            this.errorMessage = 'sulu_admin.reset_password_error';

            return;
        }

        if (!userStore.validatePassword(this.password1 || '')) {
            this.errorMessage = 'sulu_admin.reset_password_pattern_error';

            return;
        }

        this.errorMessage = null;

        const {onSubmit} = this.props;

        onSubmit({password: this.password1 || ''});
    };

    render() {
        const inputFieldClass = classNames(
            formStyles.inputField,
            {
                [formStyles.error]: this.errorMessage !== null,
            }
        );

        return (
            <Fragment>
                <Header small={this.errorMessage !== null}>
                    {translate(this.errorMessage || 'sulu_admin.reset_password')}
                </Header>
                <form className={formStyles.form} onSubmit={this.handleSubmit}>
                    <fieldset>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.password')}
                            </div>
                            <Input
                                autocomplete="new-password"
                                icon="su-lock"
                                inputRef={this.setInputRef}
                                onChange={this.handlePassword1Change}
                                type="password"
                                valid={!this.errorMessage}
                                value={this.password1}
                            />
                        </label>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.repeat_password')}
                            </div>
                            <Input
                                autocomplete="new-password"
                                icon="su-lock"
                                onChange={this.handlePassword2Change}
                                type="password"
                                valid={!this.errorMessage}
                                value={this.password2}
                            />
                        </label>
                        {Config.passwordInfoTranslationKey &&
                            <label className={fieldStyles.descriptionLabel}>
                                {translate(Config.passwordInfoTranslationKey)}
                            </label>
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
                                {translate('sulu_admin.reset_password')}
                            </Button>
                        </div>
                    </fieldset>
                </form>
            </Fragment>
        );
    }
}

export default ResetPasswordForm;
