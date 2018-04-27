// @flow
import React, {Fragment} from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {translate} from '../../utils';
import Button from '../../components/Button';
import Input from '../../components/Input';
import Header from './Header';
import formStyles from './form.scss';

type Props = {
    loading: boolean,
    user: ?string,
    onSubmit: (event: SyntheticEvent<HTMLFormElement>) => void,
    onChangeForm: () => void,
    onUserChange: (user: ?string) => void,
    error: ?string,
    success: ?string,
};

@observer
export default class ResetForm extends React.Component<Props> {
    @computed get submitButtonDisabled(): boolean {
        return !!this.props.error || !(this.props.user);
    }

    renderHeader() {
        if (this.props.error) {
            return (
                <Header small={true}>
                    {this.props.error}
                </Header>
            );
        }

        if (this.props.success) {
            return (
                <Header small={true}>
                    {translate('sulu_admin.reset_password_success')}<br /><br />
                    <b>{this.props.success}</b>
                </Header>
            );
        }

        return (
            <Header>
                {translate('sulu_admin.welcome')}
            </Header>
        );
    }

    render() {
        const resetFormClass = classNames(
            formStyles.form,
            formStyles.resetForm
        );

        return (
            <Fragment>
                {this.renderHeader()}
                <form className={resetFormClass} onSubmit={this.props.onSubmit}>
                    <fieldset>
                        <label className={formStyles.inputField}>
                            <div className={formStyles.labelText}>
                                {translate('sulu_admin.username_or_email')}
                            </div>
                            <Input icon="su-user" value={this.props.user} onChange={this.props.onUserChange} />
                        </label>
                        <div className={formStyles.buttons}>
                            <Button skin="link" onClick={this.props.onChangeForm}>
                                {translate('sulu_admin.to_login')}
                            </Button>
                            <Button
                                disabled={this.submitButtonDisabled}
                                type="submit"
                                skin="primary"
                                loading={this.props.loading}
                            >
                                {translate('sulu_admin.reset')}
                            </Button>
                        </div>
                    </fieldset>
                </form>
            </Fragment>
        );
    }
}
