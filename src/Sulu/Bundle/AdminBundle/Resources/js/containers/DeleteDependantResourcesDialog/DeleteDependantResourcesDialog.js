// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {toJS, action, computed, observable} from 'mobx';
import equals from 'fast-deep-equal';
import Dialog from '../../components/Dialog';
import {type SnackbarType} from '../../components/Snackbar';
import ProgressBar from '../../components/ProgressBar';
import ResourceRequester from '../../services/ResourceRequester';
import RequestPromise from '../../services/Requester/RequestPromise';
import {translate} from '../../utils';
import styles from './deleteDependantResourcesDialogStyles.scss';
import type {Resource, DependantResourcesData, DependantResourceBatches} from '../../types';

type Props = {
    dependantResourcesData: DependantResourcesData,
    onCancel?: () => void,
    onClose?: () => void,
    onError?: (error: any) => void,
    onFinish?: () => void,
    requestOptions?: {[string]: any} | null,
}

@observer
class DeleteDependantResourcesDialog extends React.Component<Props> {
    @observable inProgress: boolean = false;
    @observable cancelled: boolean = false;
    @observable finished: boolean = false;
    @observable showSnackbar: boolean = true;
    @observable error: string | typeof undefined = undefined;
    @observable totalDeletedResources: number = 0;

    promises: Array<RequestPromise<any>> = [];

    @computed get dependantResourceBatches(): DependantResourceBatches {
        return this.props.dependantResourcesData.dependantResources;
    }

    @computed get dependantResourcesCount(): number {
        return this.props.dependantResourcesData.dependantResourcesCount;
    }

    @action componentDidUpdate(prevProps: $ReadOnly<Props>) {
        if (!equals(toJS(prevProps.dependantResourcesData), toJS(this.props.dependantResourcesData))
            || !equals(toJS(prevProps.requestOptions), toJS(this.props.requestOptions))) {
            this.inProgress = false;
            this.cancelled = false;
            this.finished = false;
            this.showSnackbar = true;
            this.error = undefined;
            this.totalDeletedResources = 0;
            this.promises = [];
        }
    }

    @computed get errored() {
        return !!this.error;
    }

    @action handleConfirm = () => {
        const {onFinish, onError} = this.props;

        this.inProgress = true;

        this.deleteResourceBatches(this.dependantResourceBatches)
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
                    this.error = error.detail || error.title || error.message;

                    if (!onError) {
                        return;
                    }

                    onError(error);
                }));
            });
    };

    deleteResourceBatches = (batchedResources: DependantResourceBatches): Promise<void> => {
        const {requestOptions} = this.props;

        if (batchedResources.length === 0) {
            return Promise.resolve();
        }

        const [currentBatch, ...remainingBatches] = batchedResources;

        currentBatch.forEach((resource: Resource) => {
            const promise = ResourceRequester.delete(resource.resourceKey, {
                ...requestOptions,
                id: resource.id,
            });

            promise
                .then(action(() => {
                    this.totalDeletedResources++;
                }))
                .catch(() => {
                    // Ignore exception here, because it is being caught in `handleConfirm`
                    // This just prevents an `Uncaught (in promise)` exception to be thrown
                });

            this.promises.push(promise);
        });

        return Promise.all(this.promises)
            .then(() => {
                this.promises.splice(0, this.promises.length);

                if (!this.inProgress) {
                    return;
                }

                return this.deleteResourceBatches(remainingBatches);
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
                    count: this.dependantResourcesCount,
                })}
            >
                {!this.inProgress && !this.cancelled && !this.finished && !this.errored && (
                    <p>
                        {translate('sulu_admin.delete_dependants_warning', {
                            count: this.dependantResourcesCount,
                        })}
                    </p>
                )}

                {(this.inProgress || this.cancelled || this.finished || this.errored) && (
                    <React.Fragment>
                        <div className={styles.progressBar}>
                            <ProgressBar
                                max={this.dependantResourcesCount}
                                type={this.errored
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
                                count: `${this.totalDeletedResources}/${this.dependantResourcesCount}`,
                            })}
                        </p>
                    </React.Fragment>
                )}
            </Dialog>
        );
    }
}

export default DeleteDependantResourcesDialog;
