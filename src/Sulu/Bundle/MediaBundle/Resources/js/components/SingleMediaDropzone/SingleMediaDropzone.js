// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {observable, action} from 'mobx';
import classNames from 'classnames';
import Dropzone from 'react-dropzone';
import {CircularProgressbar, Icon} from 'sulu-admin-bundle/components';
import singleMediaDropzoneStyles from './singleMediaDropzone.scss';

const UPLOAD_ICON = 'cloud-upload';

type Props = {
    source: ?string,
    uploading: boolean,
    progress: number,
    onDrop: (data: File) => void,
    uploadText?: string,
};

@observer
export default class SingleMediaDropzone extends React.PureComponent<Props> {
    static defaultProps = {
        progress: 0,
        uploading: false,
    };

    @observable uploadIndicatorVisibility: boolean;

    @action setUploadIndicatorVisibility(visibility: boolean) {
        this.uploadIndicatorVisibility = visibility;
    }

    handleDrop = (files: Array<File>) => {
        const file = files[0];

        this.props.onDrop(file);
        this.setUploadIndicatorVisibility(false);
    };

    handleDragEnter = () => {
        this.setUploadIndicatorVisibility(true);
    };

    handleDragLeave = () => {
        this.setUploadIndicatorVisibility(false);
    };

    render() {
        const {
            source,
            progress,
            uploading,
            uploadText,
        } = this.props;
        const mediaContainerClass = classNames(
            singleMediaDropzoneStyles.mediaContainer,
            {
                [singleMediaDropzoneStyles.showUploadIndicator]: (!source || this.uploadIndicatorVisibility),
            }
        );

        return (
            <Dropzone
                onDrop={this.handleDrop}
                onDragEnter={this.handleDragEnter}
                onDragLeave={this.handleDragLeave}
                multiple={false}
                disableClick={uploading}
                className={mediaContainerClass}
            >
                {!uploading &&
                    <div className={singleMediaDropzoneStyles.uploadIndicatorContainer}>
                        <div className={singleMediaDropzoneStyles.uploadIndicator}>
                            <div>
                                <Icon name={UPLOAD_ICON} className={singleMediaDropzoneStyles.uploadIcon} />
                                {uploadText &&
                                    <div className={singleMediaDropzoneStyles.uploadInfoText}>{uploadText}</div>
                                }
                            </div>
                        </div>
                    </div>
                }
                {uploading &&
                    <div className={singleMediaDropzoneStyles.progressbar}>
                        <CircularProgressbar
                            size={200}
                            percentage={progress}
                        />
                    </div>
                }
                {!!source &&
                    <img className={singleMediaDropzoneStyles.thumbnail} src={source} />
                }
            </Dropzone>
        );
    }
}
