// @flow
import React from 'react';
import type {ViewProps} from '../../containers/ViewRenderer';
import Icon from '../../components/Icon';
import Input from '../../components/Input';
import Button from '../../components/Button';
import {translate} from '../../utils';
import loginStyles from './login.scss';
import logo from './logo.svg';

const BACK_BUTTON_ARROW_LEFT_ICON = 'chevron-left';

export default class Login extends React.PureComponent<ViewProps> {
    render() {
        return (
            <div className={loginStyles.login}>
                <div className={loginStyles.loginContainer}>
                    <a href="/">
                        <Icon name={BACK_BUTTON_ARROW_LEFT_ICON} />
                        {translate('sulu_admin.back_to_website')}
                    </a>
                    <img src={logo} />
                    <form className={loginStyles.form}>
                        <Input />
                        <Input />
                        <Button />
                    </form>
                </div>
            </div>
        );
    }
}
