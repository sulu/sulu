// @flow
import React from 'react';
import Dialog from '../../components/Dialog';
import {translate} from '../../utils';
import type {Resource} from '../../types';

type Props = {
    allowDeletion: boolean,
    loading: boolean,
    onCancel: () => void,
    onConfirm: () => void,
    referencingResources: Resource[],
    referencingResourcesCount: number,
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
        const {allowDeletion, loading, referencingResources} = this.props;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmLoading={loading}
                confirmText={allowDeletion ? translate('sulu_admin.delete') : translate('sulu_admin.ok')}
                onCancel={allowDeletion ? this.handleCancel : undefined}
                onConfirm={this.handleConfirm}
                open={true}
                title={allowDeletion
                    ? translate('sulu_admin.delete_linked_warning_title')
                    : translate('sulu_admin.item_not_deletable')
                }
            >
                {allowDeletion
                    ? translate('sulu_admin.delete_linked_warning_text')
                    : translate('sulu_admin.delete_linked_abort_text')
                }

                <ul>
                    {referencingResources.map((item, index) => {
                        const {title = null} = item;

                        if (!title) {
                            return null;
                        }

                        return (
                            <li key={index}>{title}</li>
                        );
                    })}
                </ul>
            </Dialog>
        );
    }
}

export default DeleteReferencedResourceDialog;
