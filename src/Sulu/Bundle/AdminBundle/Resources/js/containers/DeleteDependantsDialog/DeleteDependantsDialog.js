// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import Dialog from '../../components/Dialog';
import {type SnackbarType} from '../../components/Snackbar';
import ProgressBar from '../../components/ProgressBar';
import ResourceRequester from '../../services/ResourceRequester';
import RequestPromise from '../../services/Requester/RequestPromise';
import {translate} from '../../utils';
import styles from './deleteDependantsDialogStyles.scss';
import type {Resource} from '../../types';

type Props = {
    dependantResources: Resource[][],
    dependantResourcesCount: number,
    onCancel?: () => void,
    onClose?: () => void,
    onError?: (error: any) => void,
    onFinish?: () => void,
    requestOptions?: {[string]: any} | null,
}

@observer
class DeleteDependantsDialog extends React.Component<Props> {
    @observable inProgress: boolean = false;
    @observable cancelled: boolean = false;
    @observable finished: boolean = false;
    @observable showSnackbar: boolean = true;
    @observable error: string | null = null;
    @observable totalDeletedResources: number = 0;

    promises: Array<RequestPromise<any>> = [];

    @action componentDidUpdate(prevProps: $ReadOnly<Props>) {
        if (prevProps.dependantResources !== this.props.dependantResources
            || prevProps.dependantResourcesCount !== this.props.dependantResourcesCount
            || prevProps.requestOptions !== this.props.requestOptions) {
            this.inProgress = false;
            this.cancelled = false;
            this.finished = false;
            this.showSnackbar = true;
            this.error = null;
            this.totalDeletedResources = 0;
            this.promises = [];
        }
    }

    @computed get errored() {
        return this.error !== null;
    }

    @action handleConfirm = () => {
        const {onFinish, onError, dependantResources} = this.props;

        this.inProgress = true;

        this.deleteBatchedResources(dependantResources)
            .then(action(() => {
                this.inProgress = false;
                this.finished = true;

                if (!onFinish) {
                    return;
                }

                onFinish();
            }))
            .catch((errorResponse) => {
                errorResponse.json().then(action((error) => {
                    this.inProgress = false;
                    this.error = error.detail || error.message || 'An error occurred';

                    if (!onError) {
                        return;
                    }

                    onError(error);
                }));
            });
    };

    deleteBatchedResources = (batchedResources: Resource[][]): Promise<void> => {
        const {requestOptions} = this.props;

        if (batchedResources.length === 0) {
            return Promise.resolve();
        }

        const resources = batchedResources.shift();

        resources.forEach((resource: Resource) => {
            const promise = ResourceRequester.delete(resource.resourceKey, {
                ...requestOptions,
                id: resource.id,
            });

            promise
                .then(action(() => {
                    this.totalDeletedResources++;
                }))
                .catch(() => {
                    // ignore exception here
                });

            this.promises.push(promise);
        });

        return Promise.all(this.promises)
            .then(() => {
                this.promises.splice(0, this.promises.length);

                if (!this.inProgress) {
                    return;
                }

                return this.deleteBatchedResources(batchedResources);
            });
    };

    @action handleCancel = () => {
        const {onCancel} = this.props;

        if (this.inProgress) {
            this.inProgress = false;

            this.promises.forEach((promise: RequestPromise<any>) => {
                promise.abort();
            });
        }

        this.cancelled = true;

        if (!onCancel) {
            return;
        }

        onCancel();
    };

    handleClose = () => {
        const {onClose} = this.props;

        if (!onClose) {
            return;
        }

        onClose();
    };

    @action handleSnackbarCloseClick = () => {
        this.showSnackbar = false;
    };

    @computed get snackbarType(): SnackbarType | typeof undefined {
        if (!this.showSnackbar) {
            return undefined;
        }

        if (this.errored) {
            return 'error';
        }

        if (this.cancelled) {
            return 'warning';
        }

        return undefined;
    }

    @computed get snackbarMessage(): string | typeof undefined {
        const {snackbarType} = this;

        if (!snackbarType) {
            return undefined;
        }

        if (snackbarType === 'error' && this.error) {
            return this.error;
        }

        if (snackbarType === 'warning') {
            return translate('sulu_admin.delete_dependants_cancelled_text');
        }

        return undefined;
    }

    render() {
        const {dependantResourcesCount} = this.props;

        return (
            <Dialog
                cancelText={
                    this.errored || this.cancelled || this.finished
                        ? translate('sulu_admin.close')
                        : translate('sulu_admin.cancel')
                }
                confirmDisabled={this.errored || this.cancelled || this.finished}
                confirmLoading={this.inProgress}
                confirmText={translate('sulu_admin.delete')}
                onCancel={
                    this.errored || this.cancelled || this.finished
                        ? this.handleClose
                        : this.handleCancel
                }
                onConfirm={this.handleConfirm}
                onSnackbarCloseClick={this.handleSnackbarCloseClick}
                open={true}
                snackbarMessage={this.snackbarMessage}
                snackbarType={this.snackbarType}
                title={translate('sulu_admin.delete_dependants_warning_title', {
                    count: dependantResourcesCount,
                })}
            >
                {!this.inProgress && !this.cancelled && !this.finished && !this.errored && (
                    <p>
                        {translate('sulu_admin.delete_dependants_warning', {
                            count: dependantResourcesCount,
                        })}
                    </p>
                )}

                {(this.inProgress || this.cancelled || this.finished || this.errored) && (
                    <React.Fragment>
                        <div className={styles.progressBar}>
                            <ProgressBar
                                max={dependantResourcesCount}
                                style={this.errored
                                    ? 'error'
                                    : this.finished
                                        ? 'success'
                                        : this.cancelled
                                            ? 'warning'
                                            : 'progress'}
                                value={this.errored && this.totalDeletedResources === 0
                                    ? 1
                                    : this.totalDeletedResources
                                }
                            />
                        </div>

                        <p>
                            {translate('sulu_admin.delete_dependants_progress_text', {
                                count: `${this.totalDeletedResources}/${dependantResourcesCount}`,
                            })}
                        </p>
                    </React.Fragment>
                )}
            </Dialog>
        );
    }
}

export default DeleteDependantsDialog;
