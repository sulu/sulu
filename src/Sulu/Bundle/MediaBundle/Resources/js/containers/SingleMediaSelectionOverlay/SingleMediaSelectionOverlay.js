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
    onConfirm: (selectedMedia: Object) => void,
|};

@observer
export default class SingleMediaSelectionOverlay extends React.Component<Props> {
    static defaultProps = {
        excludedIds: [],
    };

    excludedIds: IObservableValue<Array<number>> = observable.box([]);
    collectionId: IObservableValue<?string | number> = observable.box();
    mediaListStore: ListStore;
    collectionListStore: ListStore;
    excludedIdsDisposer: () => void;
    mediaSelectionDisposer: () => void;

    constructor(props: Props) {
        super(props);

        this.mediaListStore = MediaSelectionOverlay.createMediaListStore(
            this.collectionId,
            this.excludedIds,
            this.props.locale
        );
        this.collectionListStore = MediaSelectionOverlay.createCollectionListStore(
            this.collectionId,
            this.props.locale
        );

        this.excludedIdsDisposer = autorun(() => {
            this.updateExcludedIds(this.props.excludedIds);
        });
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
        if (this.mediaListStore) {
            this.mediaListStore.destroy();
        }

        if (this.collectionListStore) {
            this.collectionListStore.destroy();
        }

        if (this.excludedIdsDisposer) {
            this.excludedIdsDisposer();
        }

        if (this.mediaSelectionDisposer) {
            this.mediaSelectionDisposer();
        }
    }

    @action updateExcludedIds(newExcludedIds: Array<number>) {
        const currentExcludedIds = toJS(this.excludedIds.get());

        if (!equal(currentExcludedIds, newExcludedIds)) {
            this.mediaDatagridStore.clear();
            this.excludedIds.set(newExcludedIds);
        }
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
