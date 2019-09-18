// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from '../../utils/index';
import Button from '../../components/Button/index';
import Input from '../../components/Input/index';
import Header from './Header';
import formStyles from './form.scss';

type Props = {|
    loading: boolean,
    onChangeForm: () => void,
    onSubmit: (user: string) => void,
    success: boolean,
|};

@observer
class ForgotPasswordForm extends React.Component<Props> {
    static defaultProps = {
        loading: false,
        success: false,
    };

    @observable inputRef: ?ElementRef<*>;

    @observable user: ?string;

    @computed get submitButtonDisabled(): boolean {
        return !this.user;
    }

    @action setInputRef = (ref: ?ElementRef<*>) => {
        this.inputRef = ref;
    };

    componentDidMount() {
        if (this.inputRef) {
            this.inputRef.focus();
        }
    }

    @action handleUserChange = (user: ?string) => {
        this.user = user;
    };

    handleSubmit = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!this.user) {
            return;
        }

        const {onSubmit} = this.props;

        onSubmit(this.user);
    };

    render() {
        const {success} = this.props;

        return (
            <Fragment>
                <Header small={success}>
                    {translate(success ? 'sulu_admin.forgot_password_success' : 'sulu_admin.forgot_password')}
                </Header>
                <form className={formStyles.form} onSubmit={this.handleSubmit}>
                    <fieldset>
                        <label className={formStyles.inputField}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.username_or_email')}
                            </div>
                            <Input
                                icon="su-user"
                                inputRef={this.setInputRef}
                                onChange={this.handleUserChange}
                                value={this.user}
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
                                {this.props.success
                                    ? translate('sulu_admin.reset_resend') : translate('sulu_admin.reset')
                                }
                            </Button>
                        </div>
                    </fieldset>
                </form>
            </Fragment>
        );
    }
}

export default ForgotPasswordForm;
