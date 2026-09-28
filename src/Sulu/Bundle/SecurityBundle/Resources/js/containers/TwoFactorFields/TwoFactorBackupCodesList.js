// @flow
import React from 'react';
import {Button} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import styles from './twoFactorFields.scss';

type Props = {|
    backupCodes: ?Array<string>,
    onCopy: () => void,
|};

export default class TwoFactorBackupCodesList extends React.Component<Props> {
    render() {
        const {backupCodes, onCopy} = this.props;

        return (
            <div className={styles.setup}>
                <p className={styles.hint}>{translate('sulu_security.two_factor_backup_codes_hint')}</p>
                <ul className={styles.backupCodes}>
                    {(backupCodes || []).map((backupCode) => (
                        <li key={backupCode}>{backupCode}</li>
                    ))}
                </ul>
                <Button icon="su-copy" onClick={onCopy} skin="link">
                    {translate('sulu_admin.copy')}
                </Button>
            </div>
        );
    }
}
