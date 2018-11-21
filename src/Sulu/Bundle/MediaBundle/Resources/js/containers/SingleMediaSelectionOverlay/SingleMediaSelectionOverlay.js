// @flow
import React from 'react';
import {autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {DatagridStore} from 'sulu-admin-bundle/containers';
import MediaSelectionOverlay from '../MediaSelectionOverlay';

type Props = {
    open: boolean,
    locale: IObservableValue<string>,
    excludedIds: Array<number>,
    onClose: () => void,
    onConfirm: (selectedMedia: Object) => void,
};

@observer
export default class SingleMediaSelectionOverlay extends React.Component<Props> {
    static defaultProps = {
        excludedIds: [],
        open: false,
    };

    collectionId: IObservableValue<?string | number> = observable.box();
    mediaDatagridStore: DatagridStore;
    collectionDatagridStore: DatagridStore;
    mediaSelectionDisposer: () => void;

    constructor(props: Props) {
        super(props);

        this.mediaDatagridStore = MediaSelectionOverlay.createMediaDatagridStore(this.collectionId, props.locale);
        this.collectionDatagridStore = MediaSelectionOverlay.createCollectionDatagridStore(
            this.collectionId,
            props.locale
        );

        this.mediaSelectionDisposer = autorun(() => {
            const {selections} = this.mediaDatagridStore;

            if (selections.length <= 1) {
                return;
            }

            const selection = selections[selections.length - 1];

            if (!selection) {
                return;
            }

            this.mediaDatagridStore.clearSelection();
            this.mediaDatagridStore.select(selection);
        });
    }

    componentWillUnmount() {
        if (this.mediaDatagridStore) {
            this.mediaDatagridStore.destroy();
        }

        if (this.collectionDatagridStore) {
            this.collectionDatagridStore.destroy();
        }

        if (this.mediaSelectionDisposer) {
            this.mediaSelectionDisposer();
        }
    }

    handleConfirm = () => {
        if (this.mediaDatagridStore.selections.length > 1) {
            throw new Error(
                'The SingleMediaSelectionOverlay can only handle single selection.'
                + 'This should not happen and is likely a bug.'
            );
        }

        this.props.onConfirm(this.mediaDatagridStore.selections[0]);
    };

    render() {
        const {
            excludedIds,
            onClose,
            open,
            locale,
        } = this.props;

        return (
            <MediaSelectionOverlay
                collectionDatagridStore={this.collectionDatagridStore}
                collectionId={this.collectionId}
                excludedIds={excludedIds}
                locale={locale}
                mediaDatagridStore={this.mediaDatagridStore}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
            />
        );
    }
}
