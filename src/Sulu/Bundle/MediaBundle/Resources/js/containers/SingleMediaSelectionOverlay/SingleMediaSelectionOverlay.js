// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
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

@observer
export default class SingleMediaSelectionOverlay extends React.Component<Props> {
    static defaultProps = {
        excludedIds: [],
    };

    collectionId: IObservableValue<?string | number> = observable.box();
    excludedIdString: IObservableValue<string>;
    mediaListStore: ListStore;
    collectionListStore: ListStore;
    mediaSelectionDisposer: () => void;

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

        if (this.mediaSelectionDisposer) {
            this.mediaSelectionDisposer();
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
