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
import {ERROR_CODE_REFERENCING_RESOURCES_FOUND} from '../../constants';
import styles from './deleteDependantResourcesDialogStyles.scss';
import type {
    Resource,
    DependantResourcesData,
    DependantResourceBatches,
    ReferencingResourcesData,
} from '../../types';

type Props = {
    dependantResourcesData: DependantResourcesData,
    onCancel?: () => void,
    onError?: (error: any) => void,
    onFinish?: () => void,
    requestOptions?: {[string]: any} | null,
}

@observer
class DeleteDependantResourcesDialog extends React.Component<Props> {
    @observable inProgress: boolean = false;
    @observable finished: boolean = false;
    @observable showSnackbar: boolean = true;
    @observable error: string | typeof undefined = undefined;
    @observable closed: boolean = false;
    @observable totalDeletedResources: number = 0;
    @observable referencingResourcesData: Array<ReferencingResourcesData> = [];

    promises: Array<RequestPromise<any>> = [];
    resolveReferencingResources: ?(confirmed: boolean) => void = undefined;

    @computed get title(): string {
        return this.props.dependantResourcesData.title;
    }

    @computed get detail(): string {
        return this.props.dependantResourcesData.detail;
    }

    @computed get dependantResourceBatches(): DependantResourceBatches {
        return this.props.dependantResourcesData.dependantResourceBatches;
    }

    @computed get dependantResourcesCount(): number {
        return this.props.dependantResourcesData.dependantResourcesCount;
    }

    @action componentDidUpdate(prevProps: $ReadOnly<Props>) {
        if (!equals(toJS(prevProps.dependantResourcesData), toJS(this.props.dependantResourcesData))
            || !equals(toJS(prevProps.requestOptions), toJS(this.props.requestOptions))) {
            this.inProgress = false;
            this.finished = false;
            this.showSnackbar = true;
            this.error = undefined;
            this.closed = false;
            this.totalDeletedResources = 0;
            this.referencingResourcesData = [];
            this.promises = [];
            this.resolveReferencingResources = undefined;
        }
    }

    @computed get errored() {
        return !!this.error;
    }

    @computed get awaitsReferencingResourcesConfirmation(): boolean {
        return this.referencingResourcesData.length > 0;
    }

    @computed get referencingResources(): Array<Resource> {
        return this.referencingResourcesData.reduce(
            (resources, {referencingResources}) => [...resources, ...referencingResources],
            []
        );
    }

    @action handleConfirm = () => {
        const {onFinish, onError} = this.props;

        if (this.resolveReferencingResources) {
            this.resolveReferencingResources(true);

            return;
        }

        this.inProgress = true;

        this.deleteResourceBatches(this.dependantResourceBatches)
            .then(action(() => {
                if (!this.inProgress) {
                    // the user cancelled the dialog before all resources were deleted
                    return;
                }

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
                    this.error = error.detail || error.title || translate('sulu_admin.unexpected_delete_server_error');

                    if (!onError) {
                        return;
                    }

                    onError(error);
                }));
            });
    };

    deleteResources = (resources: Array<Resource>, options: Object = {}): Promise<Array<Resource>> => {
        const {requestOptions} = this.props;
        const referencedResources = [];
        const referencingResourcesData = [];
        const handledPromises = [];

        resources.forEach((resource: Resource) => {
            const promise = ResourceRequester.delete(resource.resourceKey, {
                ...requestOptions,
                ...options,
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
            handledPromises.push(promise.catch((errorResponse) => {
                if (errorResponse.status !== 409) {
                    return Promise.reject(errorResponse);
                }

                return errorResponse.clone().json().then((error) => {
                    if (error.code !== ERROR_CODE_REFERENCING_RESOURCES_FOUND) {
                        return Promise.reject(errorResponse);
                    }

                    referencedResources.push(resource);
                    referencingResourcesData.push(error);
                });
            }));
        });

        return Promise.all(handledPromises)
            .then(() => {
                this.promises.splice(0, this.promises.length);

                if (referencedResources.length === 0 || !this.inProgress) {
                    return referencedResources;
                }

                // referenced resources are only deleted after the user has seen the referencing resources
                return this.confirmReferencingResources(referencingResourcesData)
                    .then((confirmed) => {
                        if (!confirmed) {
                            return referencedResources;
                        }

                        return this.deleteResources(referencedResources, {force: true});
                    });
            });
    };

    @action confirmReferencingResources = (
        referencingResourcesData: Array<ReferencingResourcesData>
    ): Promise<boolean> => {
        this.referencingResourcesData = referencingResourcesData;

        return new Promise((resolve) => {
            this.resolveReferencingResources = action((confirmed: boolean) => {
                this.referencingResourcesData = [];
                this.resolveReferencingResources = undefined;

                resolve(confirmed);
            });
        });
    };

    deleteResourceBatches = (batchedResources: DependantResourceBatches): Promise<void> => {
        if (batchedResources.length === 0) {
            return Promise.resolve();
        }

        const [currentBatch, ...remainingBatches] = batchedResources;

        return this.deleteResources(currentBatch)
            .then(() => {
                if (!this.inProgress) {
                    // do not delete next batch if user cancelled the dialog during the previous batch
                    return;
                }

                return this.deleteResourceBatches(remainingBatches);
            });
    };

    @action handleCancel = () => {
        const {onCancel} = this.props;

        if (this.inProgress) {
            this.inProgress = false;

            if (this.resolveReferencingResources) {
                this.resolveReferencingResources(false);
            }

            this.promises.forEach((promise: RequestPromise<any>) => {
                promise.abort();
            });
        }

        this.closed = true;

        if (!onCancel) {
            return;
        }

        onCancel();
    };

    @action handleSnackbarCloseClick = () => {
        this.showSnackbar = false;
    };

    @computed get snackbarType(): SnackbarType | typeof undefined {
        if (this.showSnackbar && this.errored) {
            return 'error';
        }

        return undefined;
    }

    @computed get snackbarMessage(): string | typeof undefined {
        if (this.snackbarType === 'error' && this.error) {
            return this.error;
        }

        return undefined;
    }

    render() {
        return (
            <Dialog
                cancelText={
                    this.errored || this.finished
                        ? translate('sulu_admin.close')
                        : translate('sulu_admin.cancel')
                }
                confirmDisabled={this.errored || this.finished}
                confirmLoading={this.inProgress && !this.awaitsReferencingResourcesConfirmation}
                confirmText={translate('sulu_admin.delete')}
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                onSnackbarCloseClick={this.handleSnackbarCloseClick}
                open={!this.closed}
                snackbarMessage={this.snackbarMessage}
                snackbarType={this.snackbarType}
                title={this.title}
            >
                {!this.inProgress && !this.finished && !this.errored && (
                    <p>
                        {this.detail}
                    </p>
                )}

                {this.awaitsReferencingResourcesConfirmation && (
                    <React.Fragment>
                        {translate('sulu_admin.delete_linked_warning_text')}

                        <ul>
                            {this.referencingResources.map(({title}, index) => title && <li key={index}>{title}</li>)}
                        </ul>
                    </React.Fragment>
                )}

                {(this.inProgress || this.finished || this.errored) && !this.awaitsReferencingResourcesConfirmation && (
                    <React.Fragment>
                        <div className={styles.progressBar}>
                            <ProgressBar
                                max={this.dependantResourcesCount}
                                skin={this.errored
                                    ? 'error'
                                    : this.finished
                                        ? 'success'
                                        : 'progress'}
                                value={this.errored
                                    ? this.totalDeletedResources + 1
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
