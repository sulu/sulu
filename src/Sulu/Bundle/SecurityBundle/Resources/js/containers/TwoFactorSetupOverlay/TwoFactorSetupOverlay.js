// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import QRCode from 'react-qr-code';
import {Button, Icon, Input, Overlay} from 'sulu-admin-bundle/components';
import {initializer, Requester} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import twoFactorSetupOverlayStyles from './twoFactorSetupOverlay.scss';

const SETUP_METHODS = ['totp', 'google'];

const METHOD_ICONS = {
    email: 'su-envelope',
    google: 'su-mobile',
    totp: 'su-mobile',
};

@observer
class TwoFactorSetupOverlay extends React.Component<{}> {
    static endpoints: {[string]: string} = {};
    static methods: Array<string> = [];
    static backupCodesEnabled: boolean = false;

    @observable method: ?string;
    @observable secret: ?string;
    @observable qrContent: ?string;
    @observable code: ?string;
    @observable codeValid: boolean = true;
    @observable loading: boolean = false;
    @observable backupCodes: ?Array<string>;

    @computed get open(): boolean {
        return userStore.twoFactorSetupRequired;
    }

    @computed get step(): string {
        if (this.backupCodes) {
            return 'backup-codes';
        }

        return this.qrContent ? 'setup' : 'method';
    }

    @action handleMethodClick = (method: string) => {
        this.loading = true;

        if (!SETUP_METHODS.includes(method)) {
            Requester.post(TwoFactorSetupOverlay.endpoints.twoFactorMethod, {method})
                .then(() => this.handleActivated())
                .catch(action(() => {
                    this.loading = false;
                }));

            return;
        }

        Requester.post(TwoFactorSetupOverlay.endpoints.twoFactorSetup, {method})
            .then(action((response) => {
                this.method = method;
                this.secret = response.secret;
                this.qrContent = response.qrContent;
                this.loading = false;
            }))
            .catch(action(() => {
                this.loading = false;
            }));
    };

    @action handleCodeChange = (code: ?string) => {
        this.code = code;
        this.codeValid = true;
    };

    @action handleBack = () => {
        this.method = undefined;
        this.secret = undefined;
        this.qrContent = undefined;
        this.code = undefined;
        this.codeValid = true;
    };

    @action handleActivate = () => {
        this.loading = true;

        Requester.post(TwoFactorSetupOverlay.endpoints.twoFactorConfirm, {method: this.method, code: this.code})
            .then(() => this.handleActivated())
            .catch(action(() => {
                this.loading = false;
                this.codeValid = false;
            }));
    };

    handleActivated = () => {
        if (!TwoFactorSetupOverlay.backupCodesEnabled) {
            this.finish();

            return;
        }

        Requester.post(TwoFactorSetupOverlay.endpoints.twoFactorBackupCodes)
            .then(action((response) => {
                this.backupCodes = response.backupCodes;
                this.loading = false;
            }))
            // the method is active even without backup codes, so a failure here must not
            // keep the user in the overlay
            .catch(() => this.finish());
    };

    handleBackupCodesCopy = () => {
        if (this.backupCodes) {
            void navigator.clipboard.writeText(this.backupCodes.join('\n'));
        }
    };

    // the second factor is stored on the server already, and only the reloaded config decides
    // whether the setup counts as completed
    finish = () => {
        initializer.initialize(true);
    };

    renderMethods() {
        return (
            <div className={twoFactorSetupOverlayStyles.methods}>
                <p className={twoFactorSetupOverlayStyles.hint}>
                    {translate('sulu_security.two_factor_required_hint')}
                </p>
                {TwoFactorSetupOverlay.methods.map((method) => (
                    <button
                        className={twoFactorSetupOverlayStyles.method}
                        disabled={this.loading}
                        key={method}
                        onClick={() => this.handleMethodClick(method)}
                        type="button"
                    >
                        <Icon
                            className={twoFactorSetupOverlayStyles.methodIcon}
                            name={METHOD_ICONS[method] || 'su-lock'}
                        />
                        <span className={twoFactorSetupOverlayStyles.methodText}>
                            <span className={twoFactorSetupOverlayStyles.methodTitle}>
                                {translate('sulu_security.two_factor_method_' + method)}
                            </span>
                            <span className={twoFactorSetupOverlayStyles.methodDescription}>
                                {translate('sulu_security.two_factor_method_' + method + '_description')}
                            </span>
                        </span>
                        <Icon className={twoFactorSetupOverlayStyles.methodArrow} name="su-angle-right" />
                    </button>
                ))}
            </div>
        );
    }

    renderSetup() {
        return (
            <div className={twoFactorSetupOverlayStyles.setup}>
                <p className={twoFactorSetupOverlayStyles.hint}>
                    {translate('sulu_security.two_factor_setup_scan_hint')}
                </p>
                <div className={twoFactorSetupOverlayStyles.qrCode}>
                    <QRCode size={168} value={this.qrContent || ''} />
                </div>
                <div className={twoFactorSetupOverlayStyles.section}>
                    <div className={twoFactorSetupOverlayStyles.label}>
                        {translate('sulu_security.two_factor_setup_manual_secret')}
                    </div>
                    <code className={twoFactorSetupOverlayStyles.secret}>{this.secret}</code>
                </div>
                <div className={twoFactorSetupOverlayStyles.section}>
                    <div className={twoFactorSetupOverlayStyles.label}>
                        {translate('sulu_admin.two_factor_verification_code')}
                    </div>
                    <div className={twoFactorSetupOverlayStyles.codeInput}>
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
                {TwoFactorSetupOverlay.methods.length > 1 &&
                    <Button icon="su-angle-left" onClick={this.handleBack} skin="link">
                        {translate('sulu_security.two_factor_setup_back')}
                    </Button>
                }
            </div>
        );
    }

    renderBackupCodes() {
        const {backupCodes} = this;

        return (
            <div className={twoFactorSetupOverlayStyles.setup}>
                <p className={twoFactorSetupOverlayStyles.hint}>
                    {translate('sulu_security.two_factor_backup_codes_hint')}
                </p>
                <ul className={twoFactorSetupOverlayStyles.backupCodes}>
                    {(backupCodes || []).map((backupCode) => (
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
        const {step} = this;

        // choosing a method already advances, so that step needs no confirm button
        const confirmProps = {
            'backup-codes': {
                confirmText: translate('sulu_security.two_factor_setup_finish'),
                onConfirm: this.finish,
            },
            method: {},
            setup: {
                confirmDisabled: !this.code,
                confirmText: translate('sulu_security.two_factor_setup_activate'),
                onConfirm: this.handleActivate,
            },
        }[step];

        return (
            <Overlay
                closable={false}
                confirmLoading={this.loading}
                onClose={this.finish}
                open={this.open}
                size="small"
                title={translate('sulu_security.two_factor_required_title')}
                {...confirmProps}
            >
                <Fragment>
                    {'method' === step && this.renderMethods()}
                    {'setup' === step && this.renderSetup()}
                    {'backup-codes' === step && this.renderBackupCodes()}
                </Fragment>
            </Overlay>
        );
    }
}

export default TwoFactorSetupOverlay;
