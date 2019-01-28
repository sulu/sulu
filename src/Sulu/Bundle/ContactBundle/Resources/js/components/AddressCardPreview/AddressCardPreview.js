// @flow
import React, {Fragment} from 'react';
import {translate} from 'sulu-admin-bundle/utils';
import addressCardPreviewStyles from './addressCardPreview.scss';

type Props = {|
    billingAddress: boolean,
    city: ?string,
    country: ?string,
    deliveryAddress: boolean,
    number: ?string,
    primaryAddress: boolean,
    state: ?string,
    street: ?string,
    title: ?string,
    type: string,
    zip: ?string,
|};

export default class AddressCardPreview extends React.Component<Props> {
    render() {
        const {
            billingAddress,
            country,
            city,
            deliveryAddress,
            number,
            primaryAddress,
            state,
            street,
            title,
            type,
            zip,
        } = this.props;

        const flags = [
            type,
            primaryAddress ? translate('sulu_contact.primary_address') : null,
            billingAddress ? translate('sulu_contact.billing_address') : null,
            deliveryAddress ? translate('sulu_contact.delivery_address') : null,
        ].filter((element) => element !== null);

        return (
            <section className={addressCardPreviewStyles.addressCardPreview}>
                <div className={addressCardPreviewStyles.title}>
                    <strong>{title || '\u00a0'}</strong>
                </div>

                <div className={addressCardPreviewStyles.flags}>
                    {flags.join('・')}
                </div>

                {(street || number) && <Fragment>{street} {number}<br /></Fragment>}
                {(city || zip) && <Fragment>{zip} {city}<br /></Fragment>}
                {state && <Fragment>{state}<br /></Fragment>}
                {country}
            </section>
        );
    }
}
