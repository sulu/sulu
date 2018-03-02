// @flow
import React from 'react';
import {Overlay} from '../../components';
import {translate} from '../../utils';

type Props = {
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
    title: string,
};

export default class DatagridOverlay extends React.Component<Props> {
    render() {
        const {onClose, onConfirm, open, title} = this.props;

        return (
            <Overlay
                confirmText={translate('sulu_admin.confirm')}
                onClose={onClose}
                onConfirm={onConfirm}
                open={open}
                title={title}
            >
                The datagrid will appear here
            </Overlay>
        );
    }
}
