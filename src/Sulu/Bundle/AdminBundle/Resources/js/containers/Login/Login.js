// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../../components/Icon/index';
import {translate} from '../../utils/index';
import Loader from '../../components/Loader/Loader';
import userStore from '../../stores/userStore';
import LoginForm from './LoginForm';
import ForgotPasswordForm from './ForgotPasswordForm';
import loginStyles from './login.scss';

const BACK_LINK_ARROW_LEFT_ICON = 'su-angle-left';

type Props = {|
    backLink: string,
    initialized: boolean,
    onLoginSuccess: () => void,
|};

@observer
class Login extends React.Component<Props> {
    static defaultProps = {
        backLink: '/',
        initialized: false,
    };

    @observable visibleForm: 'login' | 'forgot-password' = 'login';

    @computed get loginFormVisible(): boolean {
        return this.visibleForm === 'login';
    }

    @computed get forgotPasswordFormVisible(): boolean {
        return this.visibleForm === 'forgot-password';
    }

    @action clearState = () => {
        if (this.loginFormVisible) {
            userStore.setLoginError(false);
        } else if (this.forgotPasswordFormVisible) {
            userStore.setForgotPasswordSuccess(false);
        }
    };

    @action handleChangeToLoginForm = () => {
        this.visibleForm = 'login';
    };

    @action handleChangeToForgotPasswordForm = () => {
        this.visibleForm = 'forgot-password';
    };

    handleLoginFormSubmit = (user: string, password: string) => {
        userStore.login(user, password).then(() => {
            this.props.onLoginSuccess();
        });
    };

    handleForgotPasswordFormSubmit = (user: string) => {
        userStore.forgotPassword(user);
    };

    render() {
        const {backLink, initialized} = this.props;

        return (
            <div className={loginStyles.login}>
                <div className={loginStyles.loginContainer}>
                    <div className={loginStyles.formContainer}>
                        <div className={loginStyles.logoContainer}>
                            <Icon name="su-sulu" />
                        </div>
                        {!initialized &&
                            <div className={loginStyles.loaderContainer}>
                                <Loader size={20} />
                            </div>
                        }
                        {initialized && this.loginFormVisible &&
                            <LoginForm
                                error={userStore.loginError}
                                loading={userStore.loading}
                                onChangeForm={this.handleChangeToForgotPasswordForm}
                                onSubmit={this.handleLoginFormSubmit}
                            />
                        }
                        {initialized && this.forgotPasswordFormVisible &&
                            <ForgotPasswordForm
                                loading={userStore.loading}
                                onChangeForm={this.handleChangeToLoginForm}
                                onSubmit={this.handleForgotPasswordFormSubmit}
                                success={userStore.forgotPasswordSuccess}
                            />
                        }
                    </div>
                    <div className={loginStyles.backLinkContainer}>
                        {initialized &&
                            <a className={loginStyles.backLink} href={backLink}>
                                <Icon className={loginStyles.backLinkIcon} name={BACK_LINK_ARROW_LEFT_ICON} />
                                {translate('sulu_admin.back_to_website')}
                            </a>
                        }
                    </div>
                </div>
            </div>
        );
    }
}

export default Login;
