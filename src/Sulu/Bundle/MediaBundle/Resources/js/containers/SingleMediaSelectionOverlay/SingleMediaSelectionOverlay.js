// @flow
import React from 'react';
import {autorun, computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {ListStore} from 'sulu-admin-bundle/containers';
import MediaSelectionOverlay from '../MediaSelectionOverlay';

type Props = {|
    open: boolean,
    locale: IObservableValue<string>,
    excludedIds: Array<number>,
    onClose: () => void,
    onConfirm: (selectedMedia: Object) => void,
|};

export default @observer class SingleMediaSelectionOverlay extends React.Component<Props> {
    static defaultProps = {
        excludedIds: [],
    };

    collectionId: IObservableValue<?string | number> = observable.box();
    mediaListStore: ListStore;
    collectionListStore: ListStore;
    excludedIdsDisposer: () => void;
    mediaSelectionDisposer: () => void;

    constructor(props: Props) {
        super(props);

        const excludedIds = computed(() => this.props.excludedIds.length ? this.props.excludedIds : undefined);
        this.excludedIdsDisposer = excludedIds.observe(() => this.mediaListStore.clear());

        this.mediaListStore = MediaSelectionOverlay.createMediaListStore(
            this.collectionId,
            excludedIds,
            this.props.locale
        );
        this.collectionListStore = MediaSelectionOverlay.createCollectionListStore(
            this.collectionId,
            this.props.locale
        );

        this.mediaSelectionDisposer = autorun(() => {
            const {selections} = this.mediaListStore;

            if (selections.length <= 1) {
                return;
            }

            const selection = selections[selections.length - 1];

            if (!selection) {
                return;
            }

            this.mediaListStore.clearSelection();
            this.mediaListStore.select(selection);
        });
    }

    componentWillUnmount() {
        this.mediaListStore.destroy();
        this.collectionListStore.destroy();
        this.excludedIdsDisposer();
        this.mediaSelectionDisposer();
    }

    handleConfirm = () => {
        if (this.mediaListStore.selections.length > 1) {
            throw new Error(
                'The SingleMediaSelectionOverlay can only handle single selection.'
                + 'This should not happen and is likely a bug.'
            );
        }

        this.props.onConfirm(this.mediaListStore.selections[0]);
    };

    render() {
        const {
            onClose,
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
                onConfirm={this.handleConfirm}
                open={open}
            />
        );
    }
}
