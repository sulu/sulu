// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {MultiItemSelection} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/services';
import MediaSelectionStore from './stores/MediaSelectionStore';
import MediaSelectionOverlay from './MediaSelectionOverlay';
import MediaSelectionItem from './MediaSelectionItem';

const ADD_ICON = 'plus';

type Props = {
    locale: observable,
    value: ?Array<string | number>,
    onChange: (ids: Array<string | number>) => void,
};

@observer
export default class MediaSelection extends React.PureComponent<Props> {
    mediaSelectionStore: MediaSelectionStore;
    @observable overlayOpen: boolean = false;

    componentWillMount() {
        const {
            value,
            locale,
        } = this.props;

        this.mediaSelectionStore = new MediaSelectionStore(value, locale);
    }

    @action openMediaOverlay() {
        this.overlayOpen = true;
    }

    @action closeMediaOverlay() {
        this.overlayOpen = false;
    }

    callChangeHandler() {
        this.props.onChange(this.mediaSelectionStore.selectedMediaIds);
    }

    handleRemove = (mediaId: string | number) => {
        this.mediaSelectionStore.removeById(mediaId);
        this.callChangeHandler();
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        this.mediaSelectionStore.move(oldItemIndex, newItemIndex);
        this.callChangeHandler();
    };

    handleOverlayOpen = () => {
        this.openMediaOverlay();
    };

    handleOverlayClose = () => {
        this.closeMediaOverlay();
    };

    handleOverlayConfirm = (selectedMedia: Array<Object>) => {
        selectedMedia.forEach((media) => this.mediaSelectionStore.add(media));
        this.callChangeHandler();
        this.closeMediaOverlay();
    };

    render() {
        const {locale} = this.props;

        return (
            <div>
                <MultiItemSelection
                    label={translate('sulu_media.select_media')}
                    onItemRemove={this.handleRemove}
                    leftButton={{
                        icon: ADD_ICON,
                        onClick: this.handleOverlayOpen,
                    }}
                    onItemsSorted={this.handleSorted}
                >
                    {this.mediaSelectionStore.selectedMedia.map((selectedMedia, index) => {
                        const {
                            id,
                            title,
                            thumbnail,
                        } = selectedMedia;

                        return (
                            <MultiItemSelection.Item
                                key={id}
                                id={id}
                                index={index + 1}
                            >
                                <MediaSelectionItem thumbnail={thumbnail}>
                                    {title}
                                </MediaSelectionItem>
                            </MultiItemSelection.Item>
                        );
                    })}
                </MultiItemSelection>
                <MediaSelectionOverlay
                    open={this.overlayOpen}
                    locale={locale}
                    excludedIds={this.mediaSelectionStore.selectedMediaIds}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                />
            </div>
        );
    }
}
