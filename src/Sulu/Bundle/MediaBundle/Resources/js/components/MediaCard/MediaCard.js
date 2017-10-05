// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import {Icon, Checkbox, CroppedText} from 'sulu-admin-bundle/components';
import DownloadList from './DownloadList';
import mediaCardStyles from './mediaCard.scss';

const DOWNLOAD_ICON = 'cloud-download';

type Props = {
    id: string | number,
    selected: boolean,
    /** Called when the image at the bottom part of this element was clicked */
    onClick?: (id: string | number) => void,
    /** Called when the header or the checkbox was clicked to select/deselect this item */    
    onSelectionChange?: (id: string | number, selected: boolean) => void,
    /** The title which will be displayed in the header besides the checkbox */
    title: string,
    /** For setting meta information like the file size or extension  */
    meta?: string,
    /** The icon used inside the media overlay */
    icon?: string,
    /** The URL of the presented image */
    image: string,
    /** List of available image sizes */
    imageSizes?: Array<{url: string, label: string}>,
};

@observer
export default class MediaCard extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
    };

    @observable downloadButtonRef: ElementRef<'button'>;

    @observable downloadListOpen: boolean = false;

    @action setDownloadButtonRef = (ref: ElementRef<'button'>) => {
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
        } = this.props;

        if (onClick) {
            onClick(id);
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

    render() {
        const {
            id,
            icon,
            meta,
            title,
            image,
            selected,
            imageSizes,
        } = this.props;
        const masonryClass = classNames(
            mediaCardStyles.mediaCard,
            {
                [mediaCardStyles.selected]: selected,
            }
        );

        return (
            <div className={masonryClass}>
                <div className={mediaCardStyles.header}>
                    <div
                        className={mediaCardStyles.description}
                        onClick={this.handleHeaderClick}
                    >
                        <div className={mediaCardStyles.title}>
                            <Checkbox
                                value={id}
                                checked={!!selected}
                                className={mediaCardStyles.checkbox}
                            >
                                <div className={mediaCardStyles.titleText}>
                                    <CroppedText>{title}</CroppedText>
                                </div>
                            </Checkbox>
                        </div>
                        <div className={mediaCardStyles.meta}>
                            {meta}
                        </div>
                    </div>
                    <button
                        ref={this.setDownloadButtonRef}
                        onClick={this.handleDownloadButtonClick}
                        className={mediaCardStyles.downloadButton}
                    >
                        <Icon name={DOWNLOAD_ICON} />
                    </button>
                    <DownloadList
                        open={this.downloadListOpen}
                        onClose={this.handleDownloadListClose}
                        buttonRef={this.downloadButtonRef}
                        imageSizes={imageSizes}
                    />
                </div>
                <div
                    className={mediaCardStyles.media}
                    onClick={this.handleClick}
                >
                    <img alt={title} src={image} />
                    <div className={mediaCardStyles.mediaOverlay}>
                        {!!icon &&
                            <Icon name={icon} className={mediaCardStyles.mediaIcon} />
                        }
                    </div>
                </div>
            </div>
        );
    }
}
