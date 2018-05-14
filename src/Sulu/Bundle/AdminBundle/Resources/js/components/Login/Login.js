// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../../components/Icon';
import {translate} from '../../utils';
import Loader from '../Loader/Loader';
import LoginForm from './LoginForm';
import ResetForm from './ResetForm';
import loginStyles from './login.scss';

const BACK_LINK_ARROW_LEFT_ICON = 'su-angle-left';

type Props = {
    backLink: string,
    loginError: boolean,
    resetSuccess: boolean,
    loading: boolean,
    initialized: boolean,
    onLogin: (user: string, password: string) => void,
    onResetPassword: (user: string) => void,
    onClearError: () => void,
};

@observer
export default class Login extends React.Component<Props> {
    static defaultProps = {
        backLink: '/',
        loading: false,
        loginError: false,
        initialized: false,
        resetSuccess: false,
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

    @action handleChangeToLoginForm = () => {
        this.props.onClearError();
        this.visibleForm = 'login';
    };

    @action handleChangeToResetForm = () => {
        this.props.onClearError();
        this.visibleForm = 'reset';
    };

    @action handleUserChange = (user: ?string) => {
        this.props.onClearError();
        this.user = user;
    };

    @action handlePasswordChange = (password: ?string) => {
        this.props.onClearError();
        this.password = password;
    };

    handleLoginFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.user || !this.password) {
            return;
        }

        this.props.onLogin(this.user, this.password);
    };

    handleResetFormSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.user) {
            return;
        }

        this.props.onResetPassword(this.user);
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
                    loading={this.props.loading}
                    user={this.user}
                    password={this.password}
                    onSubmit={this.handleLoginFormSubmit}
                    onUserChange={this.handleUserChange}
                    onPasswordChange={this.handlePasswordChange}
                    onChangeForm={this.handleChangeToResetForm}
                    error={this.props.loginError}
                />
            );
        }

        if (this.resetFormVisible) {
            return (
                <ResetForm
                    loading={this.props.loading}
                    user={this.user}
                    onSubmit={this.handleResetFormSubmit}
                    onUserChange={this.handleUserChange}
                    onChangeForm={this.handleChangeToLoginForm}
                    success={this.props.resetSuccess}
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
                <Icon name={BACK_LINK_ARROW_LEFT_ICON} className={loginStyles.backLinkIcon} />
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
