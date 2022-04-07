// @flow
import React, {Fragment} from 'react';
import QRCodeComponent from 'react-qr-code';
import Input from '../Input';
import qrCodeStyles from './qrcode.scss';
import type {QRCodeProps} from './types';

export default class QRCode<T: ?string | ?number> extends React.PureComponent<QRCodeProps<T>> {
    render() {
        return (
            <Fragment>
                <Input
                    {...this.props}
                />
                <QRCodeComponent
                    className={qrCodeStyles.qrcode}
                    value={this.props.value || ''}
                    viewBox='0 0 256 256'
                />
            </Fragment>
        );
    }
}
