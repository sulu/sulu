// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {Overlay} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';

type Props = {|
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
|};

@observer
export default class CropOverlay extends React.Component<Props> {
    handleClose = () => {
        this.props.onClose();
    };

    handleConfirm = () => {
        this.props.onConfirm();
    };

    render() {
        const {open} = this.props;

        return (
            <Overlay
                confirmText={translate('sulu_admin.save')}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="large"
                title={translate('sulu_media.crop')}
            >
                Test
            </Overlay>
        );
    }
}
