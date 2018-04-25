// @flow
import React from 'react';
import Icon from '../../components/Icon';
import {translate} from '../../utils';
import LoginForm from './LoginForm';
import loginStyles from './login.scss';

const BACK_LINK_ARROW_LEFT_ICON = 'su-angle-left';

type Props = {
    backLink: string,
    onLogin: (user: string, password: string) => void,
    onResetPassword: (user: string) => void,
};

export default class Login extends React.PureComponent<Props> {
    static defaultProps = {
        backLink: '/',
    };

    render() {
        const {
            backLink,
            onLogin,
            onResetPassword,
        } = this.props;

        return (
            <div className={loginStyles.login}>
                <div className={loginStyles.loginContainer}>
                    <div className={loginStyles.formContainer}>
                        <LoginForm onLogin={onLogin} onResetPassword={onResetPassword} />
                    </div>
                    <div className={loginStyles.backLinkContainer}>
                        <a className={loginStyles.backLink} href={backLink}>
                            <Icon name={BACK_LINK_ARROW_LEFT_ICON} className={loginStyles.backLinkIcon} />
                            {translate('sulu_admin.back_to_website')}
                        </a>
                    </div>
                </div>
            </div>
        );
    }
}
