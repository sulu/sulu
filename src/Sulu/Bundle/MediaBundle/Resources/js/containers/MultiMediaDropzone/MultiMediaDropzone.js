// @flow
import React from 'react';
import type {ChildrenArray} from 'react';
import {observer} from 'mobx-react';
import Dropzone from 'react-dropzone';
import {translate} from 'sulu-admin-bundle/services';
import {Icon} from 'sulu-admin-bundle/components';
import MediaItem from './MediaItem';
import multiMediaDropzoneStyles from './multiMediaDropzone.scss';

const UPLOAD_ICON = 'cloud-upload';

type Props = {
    children: ChildrenArray<*>,
    disabled?: boolean,
    onUploaded: (files: Array<File>) => void,
};

@observer
export default class MultiMediaDropzone extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
    };

    createMediaItems() {
        return (
            <li>
                <MediaItem store={{}} />
            </li>
        );
    }

    render() {
        const {children} = this.props;

        return (
            <Dropzone
                className={multiMediaDropzoneStyles.dropzone}
                disableClick={true}
            >
                <div className={multiMediaDropzoneStyles.dropArea}>
                    <div className={multiMediaDropzoneStyles.uploadInfoContainer}>
                        <div className={multiMediaDropzoneStyles.uploadInfo}>
                            <Icon name={UPLOAD_ICON} className={multiMediaDropzoneStyles.uploadIcon} />
                            <h3 className={multiMediaDropzoneStyles.uploadInfoHeadline}>
                                {translate('sulu_media.drop_files_to_upload')}
                            </h3>
                            <div className={multiMediaDropzoneStyles.uploadInfoSubline}>
                                {translate('sulu_media.click_here_to_upload')}
                            </div>
                        </div>
                    </div>
                    <ul className={multiMediaDropzoneStyles.mediaItems}>
                        {this.createMediaItems()}
                    </ul>
                </div>
                {children}
            </Dropzone>
        );
    }
}
