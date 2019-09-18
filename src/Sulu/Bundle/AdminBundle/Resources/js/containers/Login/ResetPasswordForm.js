// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils/index';
import Button from '../../components/Button/index';
import Input from '../../components/Input/index';
import formStyles from './form.scss';
import Header from './Header';

type Props = {|
    loading: boolean,
    onChangeForm: () => void,
    onSubmit: (password: string) => void,
|};

@observer
class ForgotPasswordForm extends React.Component<Props> {
    static defaultProps = {
        loading: false,
    };

    @observable inputRef: ?ElementRef<*>;

    @observable error: boolean;

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
    };

    @action handlePassword2Change = (password2: ?string) => {
        this.password2 = password2;
    };

    @action handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.password1 || !this.password2) {
            this.error = true;
            return;
        }

        if (this.password1 !== this.password2) {
            this.error = true;
            return;
        }

        this.error = false;

        const {onSubmit} = this.props;

        onSubmit(this.password1);
    };

    render() {
        const inputFieldClass = classNames(
            formStyles.inputField,
            {
                [formStyles.error]: this.error,
            }
        );

        return (
            <Fragment>
                <Header small={this.error}>
                    {translate(this.error ? 'sulu_admin.reset_password_error' : 'sulu_admin.reset_password')}
                </Header>
                <form className={formStyles.form} onSubmit={this.handleSubmit}>
                    <fieldset>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.password')}
                            </div>
                            <Input
                                icon="su-lock"
                                inputRef={this.setInputRef}
                                onChange={this.handlePassword1Change}
                                type="password"
                                valid={!this.error}
                                value={this.password1}
                            />
                        </label>
                        <label className={inputFieldClass}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.repeat_password')}
                            </div>
                            <Input
                                icon="su-lock"
                                onChange={this.handlePassword2Change}
                                type="password"
                                valid={!this.error}
                                value={this.password2}
                            />
                        </label>
                        <div className={formStyles.buttons}>
                            <Button onClick={this.props.onChangeForm} skin="link">
                                {translate('sulu_admin.to_login')}
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

export default ForgotPasswordForm;
