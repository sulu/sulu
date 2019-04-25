// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {observer} from 'mobx-react';
import {translate} from 'sulu-admin-bundle/utils';
import {Icon} from 'sulu-admin-bundle/components';
import MediaItem from './MediaItem';
import dropzoneOverlayStyles from './dropzoneOverlay.scss';

type Props = {
    children?: ChildrenArray<Element<typeof MediaItem>>,
    open: boolean,
    onClose: () => void,
    onClick: () => void,
};

@observer
export default class DropzoneOverlay extends React.Component<Props> {
    static defaultProps = {
        open: false,
    };

    handleClose = () => {
        this.props.onClose();
    };

    handleClick = (event: Event) => {
        event.stopPropagation();
        this.props.onClick();
    };

    render() {
        const {
            open,
            children,
        } = this.props;

        if (!open) {
            return null;
        }

        return (
            <div className={dropzoneOverlayStyles.dropzoneOverlay} onClick={this.handleClose}>
                <div className={dropzoneOverlayStyles.dropArea}>
                    <div className={dropzoneOverlayStyles.uploadInfoContainer}>
                        {children &&
                            <div
                                className={dropzoneOverlayStyles.uploadInfo}
                                onClick={this.handleClick}
                                role="button"
                                tabIndex="0"
                            >
                                <Icon className={dropzoneOverlayStyles.uploadIcon} name="su-upload" />
                                <div className={dropzoneOverlayStyles.uploadInfoHeadline}>
                                    {translate('sulu_media.drop_files_to_upload')}
                                </div>
                                <div className={dropzoneOverlayStyles.uploadInfoSubline}>
                                    {translate('sulu_media.click_here_to_upload')}
                                </div>
                            </div>
                        }
                    </div>
                    <ul className={dropzoneOverlayStyles.mediaItems}>
                        {children && React.Children.map(children, (mediaItem, index) => (
                            <li key={index}>{mediaItem}</li>
                        ))}
                    </ul>
                </div>
            </div>
        );
    }
}
