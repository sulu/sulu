// @flow
import React from 'react';
import type {ViewProps} from '../../containers/ViewRenderer';
import Icon from '../../components/Icon';
import Input from '../../components/Input';
import {translate} from '../../utils';
import loginStyles from './login.scss';
import logo from './logo.svg';

const BACK_LINK_ARROW_LEFT_ICON = 'chevron-left';

export default class Login extends React.PureComponent<ViewProps> {
    render() {
        return (
            <div className={loginStyles.login}>
                <div className={loginStyles.formContainer}>
                    <a className={loginStyles.backLink} href="/">
                        <Icon name={BACK_LINK_ARROW_LEFT_ICON} className={loginStyles.backLinkIcon} />
                        {translate('sulu_admin.back_to_website')}
                    </a>
                    <img className={loginStyles.logo} src={logo} />
                    <form className={loginStyles.form}>
                        <label className={loginStyles.inputField}>
                            <div className={loginStyles.labelText}>
                                {translate('sulu_admin.username_or_email')}
                            </div>
                            <Input />
                        </label>
                        <label className={loginStyles.inputField}>
                            <div className={loginStyles.labelText}>
                                {translate('sulu_admin.password')}
                            </div>
                            <Input />
                        </label>
                        <button className={loginStyles.loginButton}>{translate('sulu_admin.login')}</button>
                    </form>
                </div>
            </div>
        );
    }
}
