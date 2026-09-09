// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import QRCode from 'react-qr-code';
import {Button, Input, Overlay} from 'sulu-admin-bundle/components';
import {initializer, Requester} from 'sulu-admin-bundle/services';
import {userStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import TwoFactorMethodButton from './TwoFactorMethodButton';
import twoFactorSetupOverlayStyles from './twoFactorSetupOverlay.scss';

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
    @observable activated: boolean = false;
    @observable backupCodes: ?Array<string>;

    // guards the auto-selection of the single available method against retrying forever when
    // the setup request for it keeps failing
    autoSelectAttempted: boolean = false;

    @computed get open(): boolean {
        return userStore.twoFactorSetupRequired;
    }

    @computed get step(): string {
        if (this.backupCodes) {
            return 'backup-codes';
        }

        if (this.activated) {
            return 'backup-codes-ask';
        }

        return this.method ? 'setup' : 'method';
    }

    componentDidMount() {
        this.autoSelectMethod();
    }

    componentDidUpdate() {
        this.autoSelectMethod();
    }

    // a single available method needs no selection, so its setup starts right away
    autoSelectMethod = () => {
        if (this.autoSelectAttempted || !this.open || this.method || TwoFactorSetupOverlay.methods.length !== 1) {
            return;
        }

        this.autoSelectAttempted = true;
        this.handleMethodClick(TwoFactorSetupOverlay.methods[0]);
    };

    @action handleMethodClick = (method: string) => {
        this.loading = true;
        this.method = method;

        Requester.post(TwoFactorSetupOverlay.endpoints.twoFactorSetup, {method})
            .then(action((response) => {
                this.secret = response.secret;
                this.qrContent = response.qrContent;
                this.loading = false;
            }))
            .catch(action(() => {
                this.method = undefined;
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

    @action handleActivated = () => {
        this.loading = false;

        if (!TwoFactorSetupOverlay.backupCodesEnabled) {
            this.handleFinish();

            return;
        }

        this.activated = true;
    };

    @action handleCreateBackupCodes = () => {
        this.loading = true;

        Requester.post(TwoFactorSetupOverlay.endpoints.twoFactorBackupCodes)
            .then(action((response) => {
                this.backupCodes = response.backupCodes;
                this.loading = false;
            }))
            // the method is active even without backup codes, so a failure here must not
            // keep the user in the overlay
            .catch(() => this.handleFinish());
    };

    handleSkipBackupCodes = () => {
        this.handleFinish();
    };

    handleBackupCodesCopy = () => {
        if (this.backupCodes) {
            void navigator.clipboard.writeText(this.backupCodes.join('\n'));
        }
    };

    // the second factor is stored on the server already, and only the reloaded config decides
    // whether the setup counts as completed
    handleFinish = () => {
        initializer.initialize(true);
    };

    renderMethods() {
        return (
            <div className={twoFactorSetupOverlayStyles.methods}>
                <p className={twoFactorSetupOverlayStyles.hint}>
                    {translate('sulu_security.two_factor_required_hint')}
                </p>
                {TwoFactorSetupOverlay.methods.map((method) => (
                    <TwoFactorMethodButton
                        disabled={this.loading}
                        key={method}
                        method={method}
                        onClick={this.handleMethodClick}
                    />
                ))}
            </div>
        );
    }

    renderSetup() {
        const isEmail = this.method === 'email';

        return (
            <div className={twoFactorSetupOverlayStyles.setup}>
                <p className={twoFactorSetupOverlayStyles.hint}>
                    {translate(isEmail
                        ? 'sulu_security.two_factor_setup_email_hint'
                        : 'sulu_security.two_factor_setup_scan_hint'
                    )}
                </p>
                {!isEmail &&
                    <Fragment>
                        <div className={twoFactorSetupOverlayStyles.qrCode}>
                            <QRCode size={168} value={this.qrContent || ''} />
                        </div>
                        <div className={twoFactorSetupOverlayStyles.section}>
                            <div className={twoFactorSetupOverlayStyles.label}>
                                {translate('sulu_security.two_factor_setup_manual_secret')}
                            </div>
                            <code className={twoFactorSetupOverlayStyles.secret}>{this.secret}</code>
                        </div>
                    </Fragment>
                }
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

    renderBackupCodesAsk() {
        return (
            <div className={twoFactorSetupOverlayStyles.setup}>
                <p className={twoFactorSetupOverlayStyles.hint}>
                    {translate('sulu_security.two_factor_backup_codes_ask_hint')}
                </p>
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
                onConfirm: this.handleFinish,
            },
            'backup-codes-ask': {
                actions: [{
                    onClick: this.handleSkipBackupCodes,
                    title: translate('sulu_security.two_factor_setup_skip_backup_codes'),
                }],
                confirmText: translate('sulu_security.two_factor_setup_create_backup_codes'),
                onConfirm: this.handleCreateBackupCodes,
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
                onClose={this.handleFinish}
                open={this.open}
                size="small"
                title={translate('sulu_security.two_factor_required_title')}
                {...confirmProps}
            >
                <Fragment>
                    {step === 'method' && this.renderMethods()}
                    {step === 'setup' && this.renderSetup()}
                    {step === 'backup-codes-ask' && this.renderBackupCodesAsk()}
                    {step === 'backup-codes' && this.renderBackupCodes()}
                </Fragment>
            </Overlay>
        );
    }
}

export default TwoFactorSetupOverlay;
