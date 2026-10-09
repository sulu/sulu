// @flow
import React from 'react';
import Dialog from '../../components/Dialog';
import {translate} from '../../utils';
import ReferencingResources from './ReferencingResources';
import type {ReferencingResourcesData} from '../../types';

type Props = {
    allowDeletion: boolean,
    confirmLoading: boolean,
    onCancel: () => void,
    onConfirm: () => void,
    referencingResourcesData: ReferencingResourcesData | Array<ReferencingResourcesData>,
}

class DeleteReferencedResourceDialog extends React.PureComponent<Props> {
    static defaultProps = {
        allowDeletion: true,
    };

    handleCancel = () => {
        const {onCancel} = this.props;

        onCancel();
    };

    handleConfirm = () => {
        const {allowDeletion, onCancel, onConfirm} = this.props;

        if (!allowDeletion) {
            onCancel();

            return;
        }

        onConfirm();
    };

    render() {
        const {allowDeletion, confirmLoading, referencingResourcesData} = this.props;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={confirmLoading}
                confirmText={allowDeletion ? translate('sulu_admin.delete') : translate('sulu_admin.ok')}
                onCancel={allowDeletion ? this.handleCancel : undefined}
                onConfirm={this.handleConfirm}
                open={true}
                title={allowDeletion
                    ? translate('sulu_admin.delete_linked_warning_title')
                    : translate('sulu_admin.item_not_deletable')
                }
            >
                <ReferencingResources
                    allowDeletion={allowDeletion}
                    referencingResourcesData={referencingResourcesData}
                />
            </Dialog>
        );
    }
}

export default DeleteReferencedResourceDialog;
