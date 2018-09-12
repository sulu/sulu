// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import {Icon, Checkbox, CroppedText} from 'sulu-admin-bundle/components';
import MimeTypeIndicator from '../MimeTypeIndicator';
import DownloadList from './DownloadList';
import mediaCardStyles from './mediaCard.scss';

const DOWNLOAD_ICON = 'fa-cloud-download';

type Props = {
    id: string | number,
    selected: boolean,
    /**
     * Called when the image at the bottom part of this element was clicked.
     * Gets the new selection state passed as second argument.
     */
    onClick?: ?(id: string | number, selected: boolean) => void,
    /** Called when the header or the checkbox was clicked to select/deselect this item */
    onSelectionChange?: ?(id: string | number, selected: boolean) => void,
    /** The title which will be displayed in the header besides the checkbox */
    title: string,
    /** For setting meta information like the file size or extension  */
    meta?: string,
    /** The icon used inside the media overlay */
    icon?: string,
    /** The URL of the presented image */
    image: ?string,
    /** Mime type to determine which icon to use if no thumbnail is present */
    mimeType: string,
    /** List of available image sizes */
    imageSizes: Array<{url: string, label: string}>,
    /** For the `Item` in the "DownloadList" which will open the defined url to download the image */
    downloadUrl: string,
    downloadText: string,
    /** Called when the "Download"-item was clicked */
    onDownload?: (url: string) => void,
    /** Info text which is shown, when a download link is hovered */
    downloadCopyText: string,
    /** When true the cover is permanently shown */
    showCover: boolean,
};

@observer
export default class MediaCard extends React.Component<Props> {
    static defaultProps = {
        downloadCopyText: '',
        imageSizes: [],
        selected: false,
        showCover: false,
    };

    @observable downloadButtonRef: ?ElementRef<'button'>;

    @observable downloadListOpen: boolean = false;

    @action setDownloadButtonRef = (ref: ?ElementRef<'button'>) => {
        this.downloadButtonRef = ref;
    };

    @action openDownloadList() {
        this.downloadListOpen = true;
    }

    @action closeDownloadList() {
        this.downloadListOpen = false;
    }

    handleClick = () => {
        const {
            id,
            onClick,
            selected,
        } = this.props;

        if (onClick) {
            onClick(id, !selected);
        }
    };

    handleHeaderClick = () => {
        const {
            id,
            selected,
            onSelectionChange,
        } = this.props;

        if (onSelectionChange && id) {
            onSelectionChange(id, !selected);
        }
    };

    handleDownloadButtonClick = () => {
        this.openDownloadList();
    };

    handleDownloadListClose = () => {
        this.closeDownloadList();
    };

    handleDownload = (url: string) => {
        const {onDownload} = this.props;

        if (onDownload) {
            onDownload(url);
            this.closeDownloadList();
        }
    };

    render() {
        const {
            downloadCopyText,
            downloadText,
            downloadUrl,
            icon,
            id,
            image,
            imageSizes,
            meta,
            mimeType,
            onSelectionChange,
            selected,
            title,
            showCover,
        } = this.props;
        const mediaCardClass = classNames(
            mediaCardStyles.mediaCard,
            {
                [mediaCardStyles.selected]: !!selected,
                [mediaCardStyles.showCover]: !!showCover,
                [mediaCardStyles.noDownloadList]: !imageSizes.length,
            }
        );
        const downloadButtonClass = classNames(
            mediaCardStyles.downloadButton,
            {
                [mediaCardStyles.active]: !!this.downloadListOpen,
            }
        );

        const mediaTitle = (
            <div className={mediaCardStyles.titleText}>
                <CroppedText>{title}</CroppedText>
            </div>
        );

        return (
            <div className={mediaCardClass}>
                <div className={mediaCardStyles.header}>
                    <div
                        className={mediaCardStyles.description}
                        onClick={this.handleHeaderClick}
                    >
                        <div className={mediaCardStyles.title}>
                            {onSelectionChange
                                ? <Checkbox
                                    checked={!!selected}
                                    className={mediaCardStyles.checkbox}
                                    value={id}
                                >
                                    {mediaTitle}
                                </Checkbox>
                                : mediaTitle
                            }
                        </div>
                        {meta &&
                            <div className={mediaCardStyles.meta}>
                                <CroppedText>{meta}</CroppedText>
                            </div>
                        }
                    </div>
                    {(!!imageSizes.length && !!downloadUrl && !!downloadText) &&
                        <div>
                            <button
                                className={downloadButtonClass}
                                onClick={this.handleDownloadButtonClick}
                                ref={this.setDownloadButtonRef}
                            >
                                <Icon name={DOWNLOAD_ICON} />
                            </button>
                            <DownloadList
                                buttonRef={this.downloadButtonRef}
                                copyText={downloadCopyText}
                                downloadText={downloadText}
                                downloadUrl={downloadUrl}
                                imageSizes={imageSizes}
                                onClose={this.handleDownloadListClose}
                                onDownload={this.handleDownload}
                                open={this.downloadListOpen}
                            />
                        </div>
                    }
                </div>
                <div
                    className={mediaCardStyles.media}
                    onClick={this.handleClick}
                >
                    {image
                        ? <img alt={title} src={image} />
                        : <MimeTypeIndicator height={200} mimeType={mimeType} />
                    }
                    <div className={mediaCardStyles.cover}>
                        {!!icon &&
                            <Icon className={mediaCardStyles.mediaIcon} name={icon} />
                        }
                    </div>
                </div>
            </div>
        );
    }
}
