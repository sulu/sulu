// @flow
import React, {Fragment} from 'react';
import QRCode from 'react-qr-code';
import {Input} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';

type Props = {|
    code: ?string,
    codeValid: boolean,
    hint: string,
    onCodeChange: (code: ?string) => void,
    qrContent: ?string,
    secret: ?string,
    showSecret: boolean,
    styles: {[string]: string},
|};

// shared by the profile field and the forced setup overlay, both of which guide the user
// through scanning a QR code (or skip that for methods without one) and entering the code
export default class TwoFactorSetupFields extends React.Component<Props> {
    static defaultProps = {
        codeValid: true,
        showSecret: true,
    };

    render() {
        const {code, codeValid, hint, onCodeChange, qrContent, secret, showSecret, styles} = this.props;

        return (
            <div className={styles.setup}>
                <p className={styles.hint}>{hint}</p>
                {showSecret &&
                    <Fragment>
                        <div className={styles.qrCode}>
                            <QRCode size={168} value={qrContent || ''} />
                        </div>
                        <div className={styles.section}>
                            <div className={styles.label}>
                                {translate('sulu_security.two_factor_setup_manual_secret')}
                            </div>
                            <code className={styles.secret}>{secret}</code>
                        </div>
                    </Fragment>
                }
                <div className={styles.section}>
                    <div className={styles.label}>
                        {translate('sulu_admin.two_factor_verification_code')}
                    </div>
                    <div className={styles.codeInput}>
                        <Input
                            alignment="center"
                            autocomplete="one-time-code"
                            inputMode="numeric"
                            onChange={onCodeChange}
                            valid={codeValid}
                            value={code}
                        />
                    </div>
                </div>
            </div>
        );
    }
}
