// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {observable, action} from 'mobx';
import classNames from 'classnames';
import Dropzone from 'react-dropzone';
import {Icon} from 'sulu-admin-bundle/components';
import SingleMediaUploadStore from './stores/SingleMediaUploadStore';
import singleMediaUploadStyles from './singleMediaUpload.scss';

const UPLOAD_ICON = 'cloud-upload';

type Props = {
    url: string,
    loading: boolean,
    progress: number,
    onUpload: (data: Object) => void,
};

@observer
export default class SingleMediaUpload extends React.PureComponent<Props> {
    static defaultProps = {
        loading: false,
        progress: 0,
    };

    store: SingleMediaUploadStore;

    @observable uploadIndicatorVisibility: boolean;

    @action setUploadIndicatorVisibility(visibility: boolean) {
        this.uploadIndicatorVisibility = visibility;
    }

    handleDragEnter = () => {
        this.setUploadIndicatorVisibility(true);
    };

    handleDragLeave = () => {
        this.setUploadIndicatorVisibility(false);
    };

    render() {
        const {
            url,
            loading,
        } = this.props;
        const hasUploadIndicatorClass = !loading && (!url || this.uploadIndicatorVisibility);
        const mediaContainerClass = classNames(
            singleMediaUploadStyles.mediaContainer,
            {
                [singleMediaUploadStyles.showUploadIndicator]: hasUploadIndicatorClass,
            }
        );

        return (
            <Dropzone
                onDrop={this.handleDrop}
                onDragEnter={this.handleDragEnter}
                onDragLeave={this.handleDragLeave}
                multiple={false}
                className={mediaContainerClass}
            >
                <div className={singleMediaUploadStyles.uploadIndicatorContainer}>
                    <div className={singleMediaUploadStyles.uploadIndicator}>
                        <Icon name={UPLOAD_ICON} className={singleMediaUploadStyles.uploadIcon} />
                    </div>
                </div>
                {!!url &&
                    <img className={singleMediaUploadStyles.thumbnail} src={url} />
                }
            </Dropzone>
        );
    }
}
