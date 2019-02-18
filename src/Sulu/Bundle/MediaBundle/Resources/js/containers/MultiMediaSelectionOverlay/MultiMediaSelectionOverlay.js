// @flow
import React from 'react';
import {action, observable} from 'mobx';
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
    excludedIdString: IObservableValue<string>;
    mediaListStore: ListStore;
    collectionListStore: ListStore;

    constructor(props: Props) {
        super(props);

        this.excludedIdString = observable.box(props.excludedIds.sort().join(','));

        this.mediaListStore = MediaSelectionOverlay.createMediaListStore(
            this.collectionId,
            this.excludedIdString,
            props.locale
        );
        this.collectionListStore = MediaSelectionOverlay.createCollectionListStore(
            this.collectionId,
            props.locale
        );
    }

    @action componentDidUpdate() {
        const newExcludedIdString = this.props.excludedIds.sort().join(',');

        if (this.excludedIdString.get() !== newExcludedIdString) {
            this.mediaListStore.clear();
            this.excludedIdString.set(this.props.excludedIds.sort().join(','));
        }
    }

    componentWillUnmount() {
        if (this.mediaListStore) {
            this.mediaListStore.destroy();
        }

        if (this.collectionListStore) {
            this.collectionListStore.destroy();
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
