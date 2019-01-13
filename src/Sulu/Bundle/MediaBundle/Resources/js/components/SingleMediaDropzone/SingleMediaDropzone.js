// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {observable, action} from 'mobx';
import classNames from 'classnames';
import Dropzone from 'react-dropzone';
import {CircularProgressbar, Icon} from 'sulu-admin-bundle/components';
import MimeTypeIndicator from '../MimeTypeIndicator';
import singleMediaDropzoneStyles from './singleMediaDropzone.scss';

const UPLOAD_ICON = 'fa-cloud-upload';
const ERROR_ICON = 'su-exclamation-triangle';

type Props = {|
    disabled: boolean,
    emptyIcon: string,
    image: ?string,
    mimeType: ?string,
    uploading: boolean,
    progress: number,
    onDrop: (data: File) => void,
    skin: 'default' | 'round',
    uploadText?: ?string,
    hasError ?: ?boolean,
    errorMessage?: ?string,
|};

@observer
export default class SingleMediaDropzone extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        emptyIcon: 'su-image',
        errorMessage: '',
        hasError: false,
        mimeType: '',
        progress: 0,
        skin: 'default',
        uploading: false,
    };

    @observable uploadIndicatorVisibility: boolean;
    @observable errorMessageVisibility: boolean;

    @action setUploadIndicatorVisibility(visibility: boolean) {
        this.uploadIndicatorVisibility = visibility;
    }

    @action setErrorMessageVisibility(visibility: boolean) {
        this.errorMessageVisibility = visibility;
    }

    handleDrop = (files: Array<File>) => {
        const file = files[0];

        this.props.onDrop(file);
        this.setUploadIndicatorVisibility(false);
        this.setErrorMessageVisibility(false);
    };

    handleDragEnter = () => {
        this.setUploadIndicatorVisibility(true);
        this.setErrorMessageVisibility(false);
    };

    handleDragLeave = () => {
        this.setUploadIndicatorVisibility(false);
        this.setErrorMessageVisibility(false);
    };

    render() {
        const {
            disabled,
            emptyIcon,
            image,
            mimeType,
            progress,
            skin,
            uploading,
            uploadText,
            hasError,
            errorMessage,
        } = this.props;

        const mediaContainerClass = classNames(
            singleMediaDropzoneStyles.mediaContainer,
            singleMediaDropzoneStyles[skin],
            {
                [singleMediaDropzoneStyles.showUploadIndicator]: this.uploadIndicatorVisibility,
                [singleMediaDropzoneStyles.showErrorIndicator]: this.errorMessageVisibility,
                [singleMediaDropzoneStyles.disabled]: disabled,
            }
        );

        return (
            <Dropzone
                className={mediaContainerClass}
                disableClick={uploading}
                disabled={disabled}
                multiple={false}
                onDragEnter={this.handleDragEnter}
                onDragLeave={this.handleDragLeave}
                onDrop={this.handleDrop}
            >
                {image &&
                    <img className={singleMediaDropzoneStyles.thumbnail} key={image} src={image} />
                }
                {!image && mimeType &&
                    <div className={singleMediaDropzoneStyles.mimeTypeIndicator}>
                        <MimeTypeIndicator iconSize={100} mimeType={mimeType} />
                    </div>
                }
                {!image && !mimeType &&
                    <div className={singleMediaDropzoneStyles.emptyIndicator}>
                        <Icon name={emptyIcon} />
                    </div>
                }

                {hasError && !uploading &&
                    <div className={singleMediaDropzoneStyles.errorIndicatorContainer}>
                        <div className={singleMediaDropzoneStyles.errorIndicator}>
                            <div>
                                <Icon className={singleMediaDropzoneStyles.errorIcon} name={ERROR_ICON} />
                                {errorMessage &&
                                    <div className={singleMediaDropzoneStyles.errorInfoText}>{errorMessage}</div>
                                }
                            </div>
                        </div>
                    </div>
                }

                {!uploading
                    ? <div className={singleMediaDropzoneStyles.uploadIndicatorContainer}>
                        <div className={singleMediaDropzoneStyles.uploadIndicator}>
                            <div>
                                <Icon className={singleMediaDropzoneStyles.uploadIcon} name={UPLOAD_ICON} />
                                {uploadText &&
                                    <div className={singleMediaDropzoneStyles.uploadInfoText}>{uploadText}</div>
                                }
                            </div>
                        </div>
                    </div>
                    : <div className={singleMediaDropzoneStyles.progressbar}>
                        <CircularProgressbar
                            percentage={progress}
                            size={200}
                        />
                    </div>
                }
            </Dropzone>
        );
    }
}
