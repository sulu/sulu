// @flow
import React, {Fragment} from 'react';
import {action, toJS, observable, reaction} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import {CroppedText, MultiItemSelection} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import {MultiSelectionStore} from 'sulu-admin-bundle/stores';
import {getIconForDisplayOption, getTranslationForDisplayOption} from '../../utils/MediaSelectionHelper';
import MultiMediaSelectionOverlay from '../MultiMediaSelectionOverlay';
import MimeTypeIndicator from '../../components/MimeTypeIndicator';
import multiMediaSelectionStyle from './multiMediaSelection.scss';
import type {DisplayOption, Media} from '../../types';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {Value} from './types';

type Props = {|
    disabled: boolean,
    displayOptions: Array<DisplayOption>,
    locale: IObservableValue<string>,
    onChange: (selectedIds: Value) => void,
    onItemClick?: (itemId: number, value: ?Media) => void,
    sortable: boolean,
    types: Array<string>,
    value: Value,
|}

const MEDIA_RESOURCE_KEY = 'media';
const THUMBNAIL_SIZE = 'sulu-25x25';

@observer
class MultiMediaSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        displayOptions: [],
        sortable: true,
        types: [],
        value: {displayOption: undefined, ids: []},
    };

    mediaSelectionStore: MultiSelectionStore<number, Media>;
    changeDisposer: () => *;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {locale, value} = this.props;

        this.mediaSelectionStore = new MultiSelectionStore(MEDIA_RESOURCE_KEY, value.ids, locale);
        this.changeDisposer = reaction(
            () => (this.mediaSelectionStore.items.map((item) => item.id)),
            (loadedMediaIds: Array<number>) => {
                const {onChange, value} = this.props;

                if (!equals(toJS(value.ids), toJS(loadedMediaIds))) {
                    onChange({...value, ids: loadedMediaIds});
                }
            }
        );
    }

    componentDidUpdate() {
        const newSelectedIds = toJS(this.props.value.ids);
        const loadedSelectedIds = toJS(this.mediaSelectionStore.items.map((item) => item.id));

        newSelectedIds.sort();
        loadedSelectedIds.sort();
        if (!equals(newSelectedIds, loadedSelectedIds)) {
            this.mediaSelectionStore.loadItems(newSelectedIds);
        }
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    @action openMediaOverlay() {
        this.overlayOpen = true;
    }

    @action closeMediaOverlay() {
        this.overlayOpen = false;
    }

    getLabel(itemCount: number) {
        if (itemCount === 1) {
            return `1 ${translate('sulu_media.media_selected_singular')}`;
        } else if (itemCount > 1) {
            return `${itemCount} ${translate('sulu_media.media_selected_plural')}`;
        }

        return translate('sulu_media.select_media_plural');
    }

    handleRemove = (mediaId: number) => {
        this.mediaSelectionStore.removeById(mediaId);
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        this.mediaSelectionStore.move(oldItemIndex, newItemIndex);
    };

    handleOverlayOpen = () => {
        this.openMediaOverlay();
    };

    handleOverlayClose = () => {
        this.closeMediaOverlay();
    };

    handleOverlayConfirm = (selectedMedia: Array<Object>) => {
        this.mediaSelectionStore.set([...this.mediaSelectionStore.items, ...selectedMedia]);
        this.closeMediaOverlay();
    };

    handleDisplayOptionClick = (displayOption: ?DisplayOption) => {
        const {onChange, value} = this.props;

        onChange({...value, displayOption});
    };

    handleItemClick = (itemId: number, item: ?Media) => {
        const {onItemClick} = this.props;

        if (!onItemClick) {
            return;
        }

        onItemClick(itemId, item);
    };

    render() {
        const {locale, disabled, displayOptions, sortable, types, value} = this.props;

        const {loading, items: medias} = this.mediaSelectionStore;
        const label = (loading) ? '' : this.getLabel(medias.length);

        const rightButton = displayOptions.length > 0
            ? {
                icon: getIconForDisplayOption(value.displayOption),
                onClick: this.handleDisplayOptionClick,
                options: displayOptions.map((displayOption) => ({
                    icon: getIconForDisplayOption(displayOption),
                    label: getTranslationForDisplayOption(displayOption),
                    value: displayOption,
                })),
            }
            : undefined;

        return (
            <Fragment>
                <MultiItemSelection
                    disabled={!!disabled}
                    label={label}
                    leftButton={{
                        icon: 'su-image',
                        onClick: this.handleOverlayOpen,
                    }}
                    loading={loading}
                    onItemClick={this.handleItemClick}
                    onItemRemove={this.handleRemove}
                    onItemsSorted={this.handleSorted}
                    rightButton={rightButton}
                    sortable={sortable}
                >
                    {medias.map((media, index) => {
                        return (
                            <MultiItemSelection.Item
                                id={media.id}
                                index={index + 1}
                                key={media.id}
                                value={media}
                            >
                                <div className={multiMediaSelectionStyle.mediaItem}>
                                    {media.thumbnails && media.thumbnails[THUMBNAIL_SIZE]
                                        ? <img
                                            alt={media.title}
                                            className={multiMediaSelectionStyle.thumbnailImage}
                                            src={media.thumbnails[THUMBNAIL_SIZE]}
                                        />
                                        : <MimeTypeIndicator
                                            height={25}
                                            iconSize={16}
                                            mimeType={media.mimeType}
                                            width={25}
                                        />
                                    }
                                    <div className={multiMediaSelectionStyle.mediaTitle}>
                                        <CroppedText>{media.title}</CroppedText>
                                    </div>
                                </div>
                            </MultiItemSelection.Item>
                        );
                    })}
                </MultiItemSelection>
                <MultiMediaSelectionOverlay
                    excludedIds={medias.map((media) => media.id)}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    types={types}
                />
            </Fragment>
        );
    }
}

export default MultiMediaSelection;
