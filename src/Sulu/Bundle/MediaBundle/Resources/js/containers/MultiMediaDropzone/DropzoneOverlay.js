// @flow
import React from 'react';
import {observer} from 'mobx-react';
import Mousetrap from 'mousetrap';
import {Portal} from 'react-portal';
import {translate} from 'sulu-admin-bundle/utils';
import {Icon} from 'sulu-admin-bundle/components';
import MediaItem from './MediaItem';
import dropzoneOverlayStyles from './dropzoneOverlay.scss';
import type {ChildrenArray, Element} from 'react';

type Props = {
    children?: ChildrenArray<Element<typeof MediaItem>>,
    onClick: () => void,
    onClose: () => void,
    onDragLeave: () => void,
    open: boolean,
};

const CLOSE_OVERLAY_KEY = 'esc';

@observer
class DropzoneOverlay extends React.Component<Props> {
    static defaultProps = {
        open: false,
    };

    constructor(props: Props) {
        super(props);

        const {onClose, open} = this.props;

        if (open) {
            Mousetrap.bind(CLOSE_OVERLAY_KEY, onClose);
        }
    }

    componentWillUnmount() {
        if (this.props.open) {
            Mousetrap.unbind(CLOSE_OVERLAY_KEY);
        }
    }

    componentDidUpdate(prevProps: Props) {
        const {onClose, open} = this.props;

        if (prevProps.open !== open) {
            if (this.props.open) {
                Mousetrap.bind(CLOSE_OVERLAY_KEY, onClose);
            } else {
                Mousetrap.unbind(CLOSE_OVERLAY_KEY);
            }
        }
    }

    handleClose = () => {
        this.props.onClose();
    };

    handleClick = (event: Event) => {
        event.stopPropagation();
        this.props.onClick();
    };

    render() {
        const {
            onDragLeave,
            open,
            children,
        } = this.props;

        if (!open) {
            return null;
        }

        return (
            <Portal>
                <div
                    className={dropzoneOverlayStyles.dropzoneOverlay}
                    onClick={this.handleClose}
                    onDragLeave={onDragLeave}
                    role="button"
                >
                    <div
                        className={dropzoneOverlayStyles.dropArea}
                        onClick={this.handleClick}
                        role="button"
                        tabIndex="0"
                    >
                        <div className={dropzoneOverlayStyles.uploadInfoContainer}>
                            {children &&
                                <div className={dropzoneOverlayStyles.uploadInfo}>
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
                    <Icon
                        className={dropzoneOverlayStyles.closeIcon}
                        name="su-times"
                        onClick={this.handleClose}
                    />
                </div>
            </Portal>
        );
    }
}

export default DropzoneOverlay;
