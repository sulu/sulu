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

type Props = {
    loading: boolean,
    onChangeForm: () => void,
    onSubmit: (event: SyntheticEvent<HTMLFormElement>) => void,
    onUserChange: (user: ?string) => void,
    success: boolean,
    user: ?string,
};

@observer
class ResetForm extends React.Component<Props> {
    static defaultProps = {
        loading: false,
        success: false,
    };

    @observable inputRef: ?ElementRef<*>;

    @computed get submitButtonDisabled(): boolean {
        return !this.props.user;
    }

    @action setInputRef = (ref: ?ElementRef<*>) => {
        this.inputRef = ref;
    };

    componentDidMount() {
        if (this.inputRef) {
            this.inputRef.focus();
        }
    }

    renderHeader() {
        if (this.props.success) {
            return (
                <Header small={true}>
                    {translate('sulu_admin.reset_password_success')}
                </Header>
            );
        }

        return (
            <Header>
                {translate('sulu_admin.reset_password')}
            </Header>
        );
    }

    render() {
        return (
            <Fragment>
                {this.renderHeader()}
                <form className={formStyles.form} onSubmit={this.props.onSubmit}>
                    <fieldset>
                        <label className={formStyles.inputField}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.username_or_email')}
                            </div>
                            <Input
                                icon="su-user"
                                inputRef={this.setInputRef}
                                onChange={this.props.onUserChange}
                                value={this.props.user}
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

export default ResetForm;
