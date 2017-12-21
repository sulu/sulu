// @flow
import React from 'react';
import Icon from '../../components/Icon';
import {translate} from '../../utils';
import LoginForm from './LoginForm';
import loginStyles from './login.scss';
import logo from './logo.svg';

const BACK_LINK_ARROW_LEFT_ICON = 'chevron-left';

type Props = {
    onLogin: (user: string, password: string) => void,
    onResetPassword: (user: string) => void,
};

export default class Login extends React.PureComponent<Props> {
    handleLogin = (user: string, password: string) => {
        this.props.onLogin(user, password);
    };

    handleResetPassword = (user: string) => {
        this.props.onResetPassword(user);
    };

    render() {
        return (
            <div className={loginStyles.login}>
                <div className={loginStyles.formContainer}>
                    <a className={loginStyles.backLink} href="/">
                        <Icon name={BACK_LINK_ARROW_LEFT_ICON} className={loginStyles.backLinkIcon} />
                        {translate('sulu_admin.back_to_website')}
                    </a>
                    <img className={loginStyles.logo} src={logo} />
                    <LoginForm onLogin={this.handleLogin} onResetPassword={this.handleResetPassword} />
                </div>
            </div>
        );
    }
}
