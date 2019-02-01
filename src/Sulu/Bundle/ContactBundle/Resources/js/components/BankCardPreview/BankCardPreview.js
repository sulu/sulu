// @flow
import React from 'react';
import bankCardPreviewStyles from './bankCardPreview.scss';

type Props = {|
    bankName: ?string,
    bic: string,
    iban: string,
|};

export default class BankCardPreview extends React.Component<Props> {
    render() {
        const {bankName, bic, iban} = this.props;

        return (
            <section className={bankCardPreviewStyles.bankCardPreview}>
                <div className={bankCardPreviewStyles.bankName}>
                    <strong>{bankName || '\u00a0'}</strong>
                </div>

                {iban}<br />
                {bic}
            </section>
        );
    }
}
