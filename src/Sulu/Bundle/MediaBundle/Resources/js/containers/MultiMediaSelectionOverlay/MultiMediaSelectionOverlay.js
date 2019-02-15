// @flow
import React from 'react';
import {action, autorun, observable, toJS} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {ListStore} from 'sulu-admin-bundle/containers';
import equal from 'fast-deep-equal';
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

    excludedIds: IObservableValue<?Array<number>> = observable.box();
    collectionId: IObservableValue<?string | number> = observable.box();
    mediaListStore: ListStore;
    collectionListStore: ListStore;
    excludedIdsDisposer: () => void;

    constructor(props: Props) {
        super(props);

        this.mediaListStore = MediaSelectionOverlay.createMediaListStore(
            this.collectionId,
            this.excludedIds,
            props.locale
        );
        this.collectionListStore = MediaSelectionOverlay.createCollectionListStore(
            this.collectionId,
            props.locale
        );

        this.excludedIdsDisposer = autorun(() => {
            this.updateExcludedIds(this.props.excludedIds);
        });
    }

    componentWillUnmount() {
        if (this.mediaListStore) {
            this.mediaListStore.destroy();
        }

        if (this.collectionListStore) {
            this.collectionListStore.destroy();
        }

        if (this.excludedIdsDisposer) {
            this.excludedIdsDisposer();
        }
    }

    @action updateExcludedIds(excludedIds: Array<number>) {
        const currentExcludedIds = toJS(this.excludedIds.get());
        const newExcludedIds = excludedIds.length ? excludedIds : undefined;

        if (!equal(currentExcludedIds, newExcludedIds)) {
            this.mediaListStore.clear();
            this.excludedIds.set(newExcludedIds);
        }
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
