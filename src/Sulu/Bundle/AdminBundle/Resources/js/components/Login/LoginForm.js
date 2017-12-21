// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils';
import Input from '../../components/Input';
import loginFormStyles from './loginForm.scss';

type Props = {
    onLogin: (user: string, password: string) => void,
    onResetPassword: (user: string) => void,
};

@observer
export default class LoginForm extends React.PureComponent<Props> {
    @observable visibleForm: 'login' | 'reset' = 'login';

    @observable user: string;

    @observable password: string;

    @action handleChangeToLoginForm = () => {
        this.visibleForm = 'login';
    };

    @action handleChangeToResetForm = () => {
        this.visibleForm = 'reset';
    };

    @action handleUserChange = (user: string) => {
        this.user = user;
    };

    @action handlePasswordChange = (password: string) => {
        this.password = password;
    };

    handleLoginFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        this.props.onLogin(this.user, this.password);
        event.preventDefault();
    };

    handleResetFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        this.props.onResetPassword(this.user);
        event.preventDefault();
    };

    render() {
        const loginFormClass = classNames(
            loginFormStyles.form,
            loginFormStyles.loginForm,
            {
                [loginFormStyles.visible]: this.visibleForm === 'login',
            }
        );
        const resetFormClass = classNames(
            loginFormStyles.form,
            loginFormStyles.resetForm,
            {
                [loginFormStyles.visible]: this.visibleForm === 'reset',
            }
        );

        return (
            <div>
                <form className={loginFormClass} onSubmit={this.handleLoginFormSubmit}>
                    <label className={loginFormStyles.inputField}>
                        <div className={loginFormStyles.labelText}>
                            {translate('sulu_admin.username_or_email')}
                        </div>
                        <Input value={this.user} onChange={this.handleUserChange} />
                    </label>
                    <label className={loginFormStyles.inputField}>
                        <div className={loginFormStyles.labelText}>
                            {translate('sulu_admin.password')}
                        </div>
                        <Input value={this.password} onChange={this.handlePasswordChange} />
                        <button
                            type="button"
                            className={loginFormStyles.changeFormButton}
                            onClick={this.handleChangeToResetForm}
                        >
                            {translate('sulu_admin.forgot_password')}
                        </button>
                    </label>
                    <button className={loginFormStyles.submit}>{translate('sulu_admin.login')}</button>
                </form>
                <form className={resetFormClass} onSubmit={this.handleResetFormSubmit}>
                    <label className={loginFormStyles.inputField}>
                        <div className={loginFormStyles.labelText}>
                            {translate('sulu_admin.username_or_email')}
                        </div>
                        <Input value={this.user} onChange={this.handleUserChange} />
                        <button
                            type="button"
                            className={loginFormStyles.changeFormButton}
                            onClick={this.handleChangeToLoginForm}
                        >
                            {translate('sulu_admin.to_login')}
                        </button>
                    </label>
                    <button className={loginFormStyles.submit}>{translate('sulu_admin.reset')}</button>
                </form>
            </div>
        );
    }
}
