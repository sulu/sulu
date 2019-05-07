// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../../components/Icon/index';
import {translate} from '../../utils/index';
import Loader from '../../components/Loader/Loader';
import userStore from '../../stores/UserStore';
import LoginForm from './LoginForm';
import ResetForm from './ResetForm';
import loginStyles from './login.scss';

const BACK_LINK_ARROW_LEFT_ICON = 'su-angle-left';

type Props = {
    backLink: string,
    onLoginSuccess: () => void,
    initialized: boolean,
};

export default @observer class Login extends React.Component<Props> {
    static defaultProps = {
        backLink: '/',
        initialized: false,
    };

    @observable visibleForm: 'login' | 'reset' = 'login';
    @observable user: ?string;
    @observable password: ?string;

    @computed get loginFormVisible(): boolean {
        return this.visibleForm === 'login';
    }

    @computed get resetFormVisible(): boolean {
        return this.visibleForm === 'reset';
    }

    @action clearState = () => {
        if (this.loginFormVisible) {
            userStore.setLoginError(false);
        } else if (this.resetFormVisible) {
            userStore.setResetSuccess(false);
        }
    };

    @action handleChangeToLoginForm = () => {
        this.visibleForm = 'login';
    };

    @action handleChangeToResetForm = () => {
        this.visibleForm = 'reset';
    };

    @action handleUserChange = (user: ?string) => {
        this.clearState();
        this.user = user;
    };

    @action handlePasswordChange = (password: ?string) => {
        this.clearState();
        this.password = password;
    };

    handleLoginFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.user || !this.password) {
            return;
        }

        userStore.login(this.user, this.password).then(() => {
            this.props.onLoginSuccess();
        });
    };

    handleResetFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.user) {
            return;
        }

        userStore.resetPassword(this.user);
    };

    renderForm() {
        if (!this.props.initialized) {
            return (
                <div className={loginStyles.loaderContainer}>
                    <Loader size={20} />
                </div>
            );
        }

        if (this.loginFormVisible) {
            return (
                <LoginForm
                    error={userStore.loginError}
                    loading={userStore.loading}
                    onChangeForm={this.handleChangeToResetForm}
                    onPasswordChange={this.handlePasswordChange}
                    onSubmit={this.handleLoginFormSubmit}
                    onUserChange={this.handleUserChange}
                    password={this.password}
                    user={this.user}
                />
            );
        }

        if (this.resetFormVisible) {
            return (
                <ResetForm
                    loading={userStore.loading}
                    onChangeForm={this.handleChangeToLoginForm}
                    onSubmit={this.handleResetFormSubmit}
                    onUserChange={this.handleUserChange}
                    success={userStore.resetSuccess}
                    user={this.user}
                />
            );
        }
    }

    renderBackLink() {
        const {backLink, initialized} = this.props;

        if (!initialized) {
            return null;
        }

        return (
            <a className={loginStyles.backLink} href={backLink}>
                <Icon className={loginStyles.backLinkIcon} name={BACK_LINK_ARROW_LEFT_ICON} />
                {translate('sulu_admin.back_to_website')}
            </a>
        );
    }

    render() {
        return (
            <div className={loginStyles.login}>
                <div className={loginStyles.loginContainer}>
                    <div className={loginStyles.formContainer}>
                        <div className={loginStyles.logoContainer}>
                            <Icon name="su-sulu" />
                        </div>
                        {this.renderForm()}
                    </div>
                    <div className={loginStyles.backLinkContainer}>
                        {this.renderBackLink()}
                    </div>
                </div>
            </div>
        );
    }
}
