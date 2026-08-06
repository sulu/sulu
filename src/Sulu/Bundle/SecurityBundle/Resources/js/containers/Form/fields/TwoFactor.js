// @flow
import React, {Fragment} from 'react';
import {action, isArrayLike, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import QRCode from 'react-qr-code';
import {Button, Dialog, Input, Overlay, SingleSelect} from 'sulu-admin-bundle/components';
import {Requester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import twoFactorStyles from './twoFactor.scss';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

const SETUP_METHODS = ['totp', 'google'];

@observer
class TwoFactor extends React.Component<FieldTypeProps<?string>> {
    static endpoints: {[string]: string} = {};
    static backupCodesEnabled: boolean = false;

    @observable setupMethod: ?string;
    @observable secret: ?string;
    @observable qrContent: ?string;
    @observable code: ?string;
    @observable codeValid: boolean = true;
    @observable loading: boolean = false;

    @observable backupCodesDialogOpen: boolean = false;
    @observable backupCodesLoading: boolean = false;
    @observable backupCodes: ?Array<string>;
    @observable backupCodesGenerated: boolean = false;

    @action handleSetupOverlayClose = () => {
        this.setupMethod = undefined;
        this.secret = undefined;
        this.qrContent = undefined;
        this.code = undefined;
        this.codeValid = true;
        this.loading = false;
    };

    @action handleMethodChange = (value: ?string) => {
        const {onChange, onFinish, value: currentValue} = this.props;

        if (value === currentValue) {
            return;
        }

        if (SETUP_METHODS.includes(value)) {
            this.setupMethod = value;
            this.loading = true;

            Requester.post(TwoFactor.endpoints.twoFactorSetup, {method: value})
                .then(action((response) => {
                    this.secret = response.secret;
                    this.qrContent = response.qrContent;
                    this.loading = false;
                }))
                .catch(action(() => {
                    this.handleSetupOverlayClose();
                }));

            return;
        }

        onChange(value);
        onFinish();
    };

    @action handleCodeChange = (code: ?string) => {
        this.code = code;
    };

    @action handleSetupConfirm = () => {
        const {onChange, onFinish} = this.props;

        this.loading = true;

        Requester.post(TwoFactor.endpoints.twoFactorConfirm, {method: this.setupMethod, code: this.code})
            .then(action(() => {
                // the method is already activated on the server, but the value is intentionally set
                // as a dirty change, so the save button of the profile form gets enabled and the
                // user has a clear way to finish the process
                onChange(this.setupMethod);
                onFinish();

                this.handleSetupOverlayClose();
            }))
            .catch(action(() => {
                this.loading = false;
                this.codeValid = false;
            }));
    };

    get hasBackupCodes(): boolean {
        const {formInspector} = this.props;

        return this.backupCodesGenerated || !!formInspector.getValueByPath('/twoFactor/hasBackupCodes');
    }

    @action handleBackupCodesButtonClick = () => {
        if (!this.hasBackupCodes) {
            // no backup codes have been generated yet, so there is nothing that could be
            // invalidated and no need to warn the user before generating a new set
            this.generateBackupCodes();

            return;
        }

        this.backupCodesDialogOpen = true;
    };

    @action handleBackupCodesDialogCancel = () => {
        this.backupCodesDialogOpen = false;
    };

    handleBackupCodesDialogConfirm = () => {
        this.generateBackupCodes();
    };

    @action generateBackupCodes = () => {
        this.backupCodesLoading = true;

        Requester.post(TwoFactor.endpoints.twoFactorBackupCodes)
            .then(action((response) => {
                this.backupCodes = response.backupCodes;
                this.backupCodesGenerated = true;
                this.backupCodesDialogOpen = false;
                this.backupCodesLoading = false;
            }))
            .catch(action(() => {
                this.backupCodesDialogOpen = false;
                this.backupCodesLoading = false;
            }));
    };

    @action handleBackupCodesOverlayClose = () => {
        this.backupCodes = undefined;
    };

    handleBackupCodesCopy = () => {
        if (this.backupCodes) {
            void navigator.clipboard.writeText(this.backupCodes.join('\n'));
        }
    };

    renderSetup() {
        return (
            <div className={twoFactorStyles.setup}>
                <p className={twoFactorStyles.hint}>{translate('sulu_security.two_factor_setup_scan_hint')}</p>
                <div className={twoFactorStyles.qrCode}>
                    <QRCode size={168} value={this.qrContent || ''} />
                </div>
                <div className={twoFactorStyles.section}>
                    <div className={twoFactorStyles.label}>
                        {translate('sulu_security.two_factor_setup_manual_secret')}
                    </div>
                    <code className={twoFactorStyles.secret}>{this.secret}</code>
                </div>
                <div className={twoFactorStyles.section}>
                    <div className={twoFactorStyles.label}>
                        {translate('sulu_admin.two_factor_verification_code')}
                    </div>
                    <div className={twoFactorStyles.codeInput}>
                        <Input
                            alignment="center"
                            autocomplete="one-time-code"
                            inputMode="numeric"
                            onChange={this.handleCodeChange}
                            valid={this.codeValid}
                            value={this.code}
                        />
                    </div>
                </div>
            </div>
        );
    }

    renderBackupCodes() {
        const {backupCodes} = this;

        if (!backupCodes) {
            return null;
        }

        return (
            <div className={twoFactorStyles.setup}>
                <p className={twoFactorStyles.hint}>{translate('sulu_security.two_factor_backup_codes_hint')}</p>
                <ul className={twoFactorStyles.backupCodes}>
                    {backupCodes.map((backupCode) => (
                        <li key={backupCode}>{backupCode}</li>
                    ))}
                </ul>
                <Button icon="su-copy" onClick={this.handleBackupCodesCopy} skin="link">
                    {translate('sulu_admin.copy')}
                </Button>
            </div>
        );
    }

    render() {
        const {disabled, schemaOptions, value} = this.props;
        const values = toJS(schemaOptions.values);

        if (!values || !isArrayLike(values.value)) {
            throw new Error('The "values" schema option of the TwoFactor field-type must be an array!');
        }

        return (
            <Fragment>
                <SingleSelect disabled={!!disabled} onChange={this.handleMethodChange} value={value}>
                    {/* $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array */}
                    {values.value.map(({name, title}, index) => {
                        if (typeof name !== 'string' && name !== undefined) {
                            throw new Error(
                                'The children of "values" must only contain values of type string or undefined!'
                            );
                        }

                        // it is not possible to define a param without a name in a form xml. to allow for creating an
                        // empty option, we use undefined as value if the name of a param is an empty string in the xml
                        const normalizedValue = name === '' ? undefined : name;

                        return (
                            <SingleSelect.Option key={index} value={normalizedValue}>
                                {title || name}
                            </SingleSelect.Option>
                        );
                    })}
                </SingleSelect>
                {!!value && TwoFactor.backupCodesEnabled &&
                    <div className={twoFactorStyles.actions}>
                        <Button
                            disabled={!!disabled}
                            icon="su-sync"
                            loading={this.backupCodesLoading}
                            onClick={this.handleBackupCodesButtonClick}
                            skin="link"
                        >
                            {translate('sulu_security.two_factor_backup_codes_generate')}
                        </Button>
                    </div>
                }
                <Overlay
                    confirmDisabled={!this.code}
                    confirmLoading={this.loading}
                    confirmText={translate('sulu_security.two_factor_setup_activate')}
                    onClose={this.handleSetupOverlayClose}
                    onConfirm={this.handleSetupConfirm}
                    open={!!this.setupMethod && !!this.qrContent}
                    size="small"
                    title={translate('sulu_security.two_factor_setup_title')}
                >
                    {this.renderSetup()}
                </Overlay>
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.backupCodesLoading}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleBackupCodesDialogCancel}
                    onConfirm={this.handleBackupCodesDialogConfirm}
                    open={this.backupCodesDialogOpen}
                    title={translate('sulu_security.two_factor_backup_codes_generate')}
                >
                    {translate('sulu_security.two_factor_backup_codes_generate_warning')}
                </Dialog>
                <Overlay
                    confirmText={translate('sulu_admin.ok')}
                    onClose={this.handleBackupCodesOverlayClose}
                    onConfirm={this.handleBackupCodesOverlayClose}
                    open={!!this.backupCodes}
                    size="small"
                    title={translate('sulu_security.two_factor_backup_codes')}
                >
                    {this.renderBackupCodes()}
                </Overlay>
            </Fragment>
        );
    }
}

export default TwoFactor;
