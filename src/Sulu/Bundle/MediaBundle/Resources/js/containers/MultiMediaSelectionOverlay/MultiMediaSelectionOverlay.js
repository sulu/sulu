// @flow
import React from 'react';
import {computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {ListStore} from 'sulu-admin-bundle/containers';
import MediaSelectionOverlay from '../MediaSelectionOverlay';

type Props = {|
    open: boolean,
    locale: IObservableValue<string>,
    excludedIds: Array<number>,
    onClose: () => void,
    onConfirm: (selectedMedia: Array<Object>) => void,
|};

@observer
export default class MultiMediaSelectionOverlay extends React.Component<Props> {
    static defaultProps = {
        excludedIds: [],
    };

    collectionId: IObservableValue<?string | number> = observable.box();
    mediaListStore: ListStore;
    collectionListStore: ListStore;
    excludedIdsDisposer: () => void;

    constructor(props: Props) {
        super(props);

        const excludedIds = computed(() => this.props.excludedIds.length ? this.props.excludedIds : undefined);
        this.excludedIdsDisposer = excludedIds.observe(() => this.mediaListStore.clear());

        this.mediaListStore = MediaSelectionOverlay.createMediaListStore(
            this.collectionId,
            excludedIds,
            props.locale
        );
        this.collectionListStore = MediaSelectionOverlay.createCollectionListStore(
            this.collectionId,
            props.locale
        );
    }

    componentWillUnmount() {
        this.mediaListStore.destroy();
        this.collectionListStore.destroy();
        this.excludedIdsDisposer();
    }

    render() {
        const {
            onClose,
            onConfirm,
            open,
            locale,
        } = this.props;

        return (
            <MediaSelectionOverlay
                collectionId={this.collectionId}
                collectionListStore={this.collectionListStore}
                locale={locale}
                mediaListStore={this.mediaListStore}
                onClose={onClose}
                onConfirm={onConfirm}
                open={open}
            />
        );
    }
}
