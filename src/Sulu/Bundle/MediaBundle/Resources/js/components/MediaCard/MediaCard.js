// @flow
import React, {Fragment} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import {Checkbox, CroppedText, GhostIndicator, Icon, Loader} from 'sulu-admin-bundle/components';
import MimeTypeIndicator from '../MimeTypeIndicator';
import DownloadList from './DownloadList';
import mediaCardStyles from './mediaCard.scss';
import type {ElementRef} from 'react';

const DOWNLOAD_ICON = 'su-download';

type Props = {|
    downloadCopyText: string,
    downloadText: string,
    downloadUrl: string,
    ghostLocale?: string,
    icon?: string,
    id: string | number,
    image: ?string,
    imageSizes: Array<{label: string, url: string}>,
    meta?: string,
    mimeType: string,
    onClick?: ?(id: string | number, selected: boolean) => void,
    onDownload?: (url: string) => void,
    onSelectionChange?: ?(id: string | number, selected: boolean) => void,
    selected: boolean,
    showCover: boolean,
    title: string,
|};

@observer
class MediaCard extends React.Component<Props> {
    static defaultProps = {
        downloadCopyText: '',
        imageSizes: [],
        selected: false,
        showCover: false,
    };

    image: Image;

    @observable downloadButtonRef: ?ElementRef<'button'>;
    @observable downloadListOpen: boolean = false;
    @observable imageLoading: boolean = true;
    @observable imageError: boolean = false;

    constructor(props: Props) {
        super(props);

        const {image: src} = this.props;

        if (src) {
            this.image = new Image();
            this.image.onload = this.handleImageLoad;
            this.image.onerror = this.handleImageError;
            this.image.src = src;
        } else {
            this.handleImageLoad();
        }
    }

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

    handleKeypress = (event: SyntheticKeyboardEvent<HTMLElement>) => {
        const {
            id,
            onClick,
            selected,
        } = this.props;

        if (!onClick) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.stopPropagation();
            onClick(id, !selected);
        }
    };

    handleHeaderClick = () => {
        const {
            id,
            selected,
            onSelectionChange,
        } = this.props;

        if (onSelectionChange) {
            onSelectionChange(id, !selected);
        }
    };

    handleHeaderKeypress = (event: SyntheticKeyboardEvent<HTMLElement>) => {
        const {
            id,
            selected,
            onSelectionChange,
        } = this.props;

        if (!onSelectionChange) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.stopPropagation();
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

    @action handleImageLoad = () => {
        this.imageLoading = false;
    };

    @action handleImageError = () => {
        this.imageError = true;
    };

    render() {
        const {
            downloadCopyText,
            downloadText,
            downloadUrl,
            ghostLocale,
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
                {ghostLocale && <GhostIndicator className={mediaCardStyles.ghostIndicator} locale={ghostLocale} />}
                <CroppedText>{title}</CroppedText>
            </div>
        );

        return (
            <div className={mediaCardClass}>
                <div className={mediaCardStyles.header}>
                    <div
                        className={mediaCardStyles.description}
                        onClick={this.handleHeaderClick}
                        onKeyPress={this.handleHeaderKeypress}
                        role="button"
                        tabIndex="0"
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
                                type="button"
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
                    onKeyPress={this.handleKeypress}
                    role="button"
                    tabIndex="0"
                >
                    {image && !this.imageError
                        ? (
                            <Fragment>
                                <img alt={title} src={this.image.src} />
                                {this.imageLoading && <Loader />}
                            </Fragment>
                        )
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

export default MediaCard;
