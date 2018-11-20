// @flow
import React, {Fragment} from 'react';
import {action, autorun, toJS, observable} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import type {IObservableValue} from 'mobx';
import MediaSelectionStore from '../../stores/MediaSelectionStore';
import MediaSelectionItem from '../../components/MediaSelectionItem';
import MultiMediaSelectionOverlay from '../MultiMediaSelectionOverlay/MultiMediaSelectionOverlay';
import type {Value} from './types';

type Props = {|
    disabled: boolean,
    locale: IObservableValue<string>,
    onChange: (selectedIds: Value) => void,
    value: Value,
|}

@observer
export default class MultiMediaSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        value: {ids: []},
    };

    mediaSelectionStore: MediaSelectionStore;
    changeDisposer: () => void;
    changeAutorunInitialized: boolean = false;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {
            locale,
            value,
        } = this.props;

        this.mediaSelectionStore = new MediaSelectionStore(value.ids, locale);
        this.changeDisposer = autorun(() => {
            const {onChange, value} = this.props;
            const loadedMediaIds = this.mediaSelectionStore.selectedMediaIds;

            if (!this.changeAutorunInitialized) {
                this.changeAutorunInitialized = true;
                return;
            }

            if (equals(toJS(value.ids), toJS(loadedMediaIds))) {
                return;
            }

            onChange({ids: loadedMediaIds});
        });
    }

    componentDidUpdate() {
        const {
            locale,
            value,
        } = this.props;

        const newSelectedIds = toJS(value.ids);
        const currentSelectedIds = toJS(this.mediaSelectionStore.selectedMediaIds);

        newSelectedIds.sort();
        currentSelectedIds.sort();
        if (!equals(newSelectedIds, currentSelectedIds) && !this.mediaSelectionStore.loading) {
            this.mediaSelectionStore.loadSelectedMedia(newSelectedIds, locale);
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
        selectedMedia.forEach((media) => this.mediaSelectionStore.add(media));
        this.closeMediaOverlay();
    };

    render() {
        const {locale, disabled} = this.props;

        const {
            loading,
            selectedMedia,
            selectedMediaIds,
        } = this.mediaSelectionStore;
        const label = (loading) ? '' : this.getLabel(selectedMedia.length);

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
                    onItemRemove={this.handleRemove}
                    onItemsSorted={this.handleSorted}
                >
                    {selectedMedia.map((selectedMedia, index) => {
                        const {
                            id,
                            title,
                            mimeType,
                            thumbnail,
                        } = selectedMedia;

                        return (
                            <MultiItemSelection.Item
                                id={id}
                                index={index + 1}
                                key={id}
                            >
                                <MediaSelectionItem mimeType={mimeType} thumbnail={thumbnail ? thumbnail : undefined}>
                                    {title}
                                </MediaSelectionItem>
                            </MultiItemSelection.Item>
                        );
                    })}
                </MultiItemSelection>
                <MultiMediaSelectionOverlay
                    excludedIds={selectedMediaIds}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                />
            </Fragment>
        );
    }
}
