// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import Dropzone, {DropzoneRef, FileRejection} from 'react-dropzone';
import symfonyRouting from 'fos-jsrouting/router';
import {translate} from '../../../utils/Translator';
import AbstractListToolbarAction from './AbstractListToolbarAction';

export default class UploadToolbarAction extends AbstractListToolbarAction {
    @observable dropzoneRef: ?DropzoneRef;
    @observable errors: string[] = [];

    @action setDropzoneRef = (ref: ?DropzoneRef) => {
        this.dropzoneRef = ref;
    };

    @action handleClick = () => {
        const {dropzoneRef} = this;

        if (dropzoneRef) {
            dropzoneRef.open();
            this.removeErrors();
        }
    };

    removeErrors = () => {
        for (const error of this.errors) {
            this.removeError(error);
        }
    };

    @action removeError = (errorToRemove: string) => {
        this.errors = this.errors.filter((existingError) => errorToRemove !== existingError);
        this.list.errors = this.list.errors.filter((existingError) => errorToRemove !== existingError);
    };

    @action addError = (error: string) => {
        this.removeError(error);

        this.errors = [...this.errors, error];
        this.list.errors = [...this.list.errors, error];
    };

    handleError = (fileRejections: FileRejection[]) => {
        for (const fileRejection of fileRejections) {
            for (const {code} of fileRejection.errors) {
                let error;
                switch (code) {
                    case 'file-invalid-type':
                        error = translate('sulu_admin.dropzone_error_file-invalid-type', {
                            fileName: fileRejection.file.name,
                            allowedTypes: this.accept ? this.accept.join(', ') : undefined,
                        });
                        break;
                    case 'file-too-large':
                        error = translate('sulu_admin.dropzone_error_file-too-large', {
                            fileName: fileRejection.file.name,
                            maxSize: this.maxSize ? this.getReadableFileSizeString(this.maxSize) : undefined,
                        });
                        break;
                    case 'file-too-small':
                        error = translate('sulu_admin.dropzone_error_file-too-small', {
                            fileName: fileRejection.file.name,
                            minSize: this.minSize ? this.getReadableFileSizeString(this.minSize) : undefined,
                        });
                        break;
                    case 'too-many-files':
                        error = translate('sulu_admin.dropzone_error_too-many-files', {
                            fileName: fileRejection.file.name,
                            maxFiles: this.maxFiles,
                        });
                        break;
                    default:
                        error = translate('sulu_admin.an_error_occurred');
                }

                this.addError(error);
            }
        }
    };

    @action handleConfirm = (files: File[]) => {
        for (const file of files) {
            const formData = new FormData();
            formData.append('redirectRoutes', file);

            fetch(this.url, {
                method: 'POST',
                body: formData,
            })
                .then((response) => {
                    if (!response.ok) {
                        const error = this.errorMapping[response.status] || 'sulu_admin.an_error_occurred';
                        this.addError(translate(error, {
                            statusText: response.statusText,
                        }));

                        return;
                    }

                    this.listStore.setShouldReload(true);
                });
        }
    };

    @computed get label(): string {
        const {label = 'sulu_admin.upload'} = this.options;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string!');
        }

        return translate(label);
    }

    @computed get icon(): string {
        const {icon = 'su-upload'} = this.options;

        if (typeof icon !== 'string') {
            throw new Error('The "icon" option must be a string!');
        }

        return icon;
    }

    @computed get url(): string {
        const {routeName} = this.options;

        if (typeof routeName !== 'string') {
            throw new Error('The "routeName" option must be a string!');
        }

        return symfonyRouting.generate(routeName, this.requestParameters);
    }

    @computed get errorMapping(): $ReadOnly<Object> {
        const {errorMapping = {}} = this.options;

        if (typeof errorMapping !== 'object') {
            throw new Error('The "errorMapping" option must be an object!');
        }

        return errorMapping;
    }

    @computed get requestParameters(): $ReadOnly<Object> {
        const {
            options: {
                requestParameters: attributesToRequest = {},
                routerAttributesToRequest = {},
            },
            router: {
                attributes: routerAttributes,
            },
        } = this;

        if (!attributesToRequest || typeof attributesToRequest !== 'object') {
            throw new Error('The "attributesToRequest" option must be an object!');
        }

        if (!routerAttributesToRequest || typeof routerAttributesToRequest !== 'object') {
            throw new Error('The "routerAttributesToRequest" option must be an object!');
        }

        let requestParameters = {};
        Object.keys(routerAttributesToRequest)
            .forEach((routerAttributeKey) => {
                const requestAttributeKey = routerAttributesToRequest[routerAttributeKey];

                if (typeof requestAttributeKey !== 'string') {
                    throw new Error('The values "routerAttributesToRequest" must be strings!');
                }

                const attributeName = isNaN(routerAttributeKey)
                    ? routerAttributeKey
                    : requestAttributeKey;

                requestParameters[requestAttributeKey] = routerAttributes[attributeName];
            });
        requestParameters = {...requestParameters, ...attributesToRequest};

        return requestParameters;
    }

    @computed get accept(): ?$ReadOnlyArray<any> {
        const {accept} = this.options;

        if (accept === undefined || accept === null) {
            return undefined;
        }

        if (!Array.isArray(accept)) {
            throw new Error('The "accept" option must be an array!');
        }

        return accept;
    }

    @computed get minSize(): ?number {
        const {minSize} = this.options;

        if (minSize === undefined || minSize === null) {
            return undefined;
        }

        if (typeof minSize !== 'number') {
            throw new Error('The "minSize" option must be a number!');
        }

        return minSize;
    }

    @computed get maxSize(): ?number {
        const {maxSize} = this.options;

        if (maxSize === undefined || maxSize === null) {
            return undefined;
        }

        if (typeof maxSize !== 'number') {
            throw new Error('The "maxSize" option must be a number!');
        }

        return maxSize;
    }

    @computed get maxFiles(): ?number {
        const {maxFiles} = this.options;

        if (maxFiles === undefined || maxFiles === null) {
            return undefined;
        }

        if (typeof maxFiles !== 'number') {
            throw new Error('The "maxFiles" option must be a number!');
        }

        return maxFiles;
    }

    @computed get multiple(): boolean {
        const {maxFiles} = this;

        return maxFiles !== 1;
    }

    getToolbarItemConfig() {
        return {
            type: 'button',
            label: this.label,
            icon: this.icon,
            onClick: this.handleClick,
        };
    }

    getNode() {
        return (
            <Dropzone
                accept={this.accept}
                key="sulu_admin.upload"
                maxFiles={this.maxFiles}
                maxSize={this.maxSize}
                minSize={this.minSize}
                multiple={this.multiple}
                noClick={true}
                noDrag={true}
                noKeyboard={true}
                onDropAccepted={this.handleConfirm}
                onDropRejected={this.handleError}
                ref={this.setDropzoneRef}
            >
                {({getRootProps, getInputProps}) => {
                    return (
                        <div {...getRootProps()}>
                            <input {...getInputProps()} />
                        </div>
                    );
                }}
            </Dropzone>
        );
    }

    // Method copied from https://stackoverflow.com/q/10420352/7733374
    getReadableFileSizeString = (fileSizeInBytes: number): string => {
        var i = -1;
        var byteUnits = [' kB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB'];
        do {
            fileSizeInBytes = fileSizeInBytes / 1024;
            i++;
        } while (fileSizeInBytes > 1024);

        return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
    };
}
