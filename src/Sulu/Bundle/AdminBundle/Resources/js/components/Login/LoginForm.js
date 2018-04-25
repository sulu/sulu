// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils';
import Button from '../../components/Button';
import Input from '../../components/Input';
import loginFormStyles from './loginForm.scss';
import logo from './logo.svg';

type Props = {
    onLogin: (user: string, password: string) => void,
    onResetPassword: (user: string) => void,
};

@observer
export default class LoginForm extends React.PureComponent<Props> {
    @observable visibleForm: 'login' | 'reset' = 'login';

    @observable user: ?string;

    @observable password: ?string;

    @action handleChangeToLoginForm = () => {
        this.visibleForm = 'login';
    };

    @action handleChangeToResetForm = () => {
        this.visibleForm = 'reset';
    };

    @action handleUserChange = (user: ?string) => {
        this.user = user;
    };

    @action handlePasswordChange = (password: ?string) => {
        this.password = password;
    };

    handleLoginFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        if (!this.user || !this.password) {
            return;
        }

        this.props.onLogin(this.user, this.password);
        event.preventDefault();
    };

    handleResetFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        if (!this.user) {
            return;
        }

        this.props.onResetPassword(this.user);
        event.preventDefault();
    };

    render() {
        const loginFormVisible = this.visibleForm === 'login';
        const resetFormVisible = this.visibleForm === 'reset';
        const loginFormClass = classNames(
            loginFormStyles.form,
            loginFormStyles.loginForm,
            {
                [loginFormStyles.visible]: loginFormVisible,
            }
        );
        const resetFormClass = classNames(
            loginFormStyles.form,
            loginFormStyles.resetForm,
            {
                [loginFormStyles.visible]: resetFormVisible,
            }
        );

        const resetButtonDisabled = !this.user;
        const loginButtonDisabled = !(this.user && this.password);

        return (
            <Fragment>
                <img className={loginFormStyles.logo} src={logo} />
                <div className={loginFormStyles.header}>Welcome</div>
                <form className={loginFormClass} onSubmit={this.handleLoginFormSubmit}>
                    <fieldset disabled={!loginFormVisible}>
                        <label className={loginFormStyles.inputField}>
                            <div className={loginFormStyles.labelText}>
                                {translate('sulu_admin.username_or_email')}
                            </div>
                            <Input icon="su-user" value={this.user} onChange={this.handleUserChange} />
                        </label>
                        <label className={loginFormStyles.inputField}>
                            <div className={loginFormStyles.labelText}>
                                {translate('sulu_admin.password')}
                            </div>
                            <Input icon="su-lock" type="password" value={this.password} onChange={this.handlePasswordChange} />
                        </label>
                        <div className={loginFormStyles.buttons}>
                            <Button skin="link" onClick={this.handleChangeToResetForm}>
                                {translate('sulu_admin.forgot_password')}
                            </Button>
                            <Button disabled={loginButtonDisabled} type="submit" skin="primary">{translate('sulu_admin.login')}</Button>
                        </div>
                    </fieldset>
                </form>
                <form className={resetFormClass} onSubmit={this.handleResetFormSubmit}>
                    <fieldset disabled={!resetFormVisible}>
                        <label className={loginFormStyles.inputField}>
                            <div className={loginFormStyles.labelText}>
                                {translate('sulu_admin.username_or_email')}
                            </div>
                            <Input icon="su-user" value={this.user} onChange={this.handleUserChange} />
                        </label>
                        <div className={loginFormStyles.buttons}>
                            <Button skin="link" onClick={this.handleChangeToLoginForm}>
                                {translate('sulu_admin.to_login')}
                            </Button>
                            <Button disabled={resetButtonDisabled} type="submit" skin="primary">{translate('sulu_admin.reset')}</Button>
                        </div>
                    </fieldset>
                </form>
            </Fragment>
        );
    }
}
