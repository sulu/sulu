// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import type {IObservableValue} from 'mobx';
import {action, autorun, observable, toJS} from 'mobx';
import SingleItemSelection from 'sulu-admin-bundle/components/SingleItemSelection';
import singleSelectionStyles from 'sulu-admin-bundle/containers/SingleSelection/singleSelection.scss';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import MediaSelectionOverlay from '../MediaSelection/MediaSelectionOverlay';
import SingleMediaSelectionStore from '../../stores/SingleMediaSelectionStore/SingleMediaSelectionStore';
import MediaSelectionItem from "../../components/MediaSelectionItem/MediaSelectionItem";

type Props = {|
    disabled: boolean,
    locale: IObservableValue<string>,
    onChange: (selectedIds: ?number) => void,
    value: ?number,
|}

@observer
export default class SingleMediaSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
    };

    singleMediaSelectionStore: SingleMediaSelectionStore;
    changeDisposer: () => void;
    changeAutorunInitialized: boolean = false;

    @observable overlayOpen: boolean = false;

    @action openOverlay() {
        this.overlayOpen = true;
    }

    constructor(props: Props) {
        super(props);

        const {onChange, locale, value} = this.props;

        this.singleMediaSelectionStore = new SingleMediaSelectionStore(value, locale);
        this.changeDisposer = autorun(() => {
            const {value} = this.props;
            const {selectedMedia} = this.singleMediaSelectionStore;
            const itemId = selectedMedia ? selectedMedia.id : undefined;

            if (!this.changeAutorunInitialized) {
                this.changeAutorunInitialized = true;
                return;
            }

            if (value === itemId) {
                return;
            }

            onChange(itemId);
        });
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    componentDidUpdate() {
        const {
            locale,
            value,
        } = this.props;

        const newValue = toJS(value);
        const oldValue = toJS(this.singleMediaSelectionStore.selectedMediaId);

        if (oldValue !== newValue) {
            this.singleMediaSelectionStore.loadSelectedMedia(newValue, locale);
        }
    }

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    @action handleOverlayOpen = () => {
        this.openOverlay();
    };

    @action handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleOverlayConfirm = (selectedMedia: Array<Object>) => {
        this.singleMediaSelectionStore.set(selectedMedia ? selectedMedia[0] : undefined);
        this.closeOverlay();
    };

    handleRemove = () => {
        this.singleMediaSelectionStore.clear();
    };

    render() {
        const {
            disabled,
            locale,
            value,
        } = this.props;
        const {
            selectedMedia,
        } = this.singleMediaSelectionStore;

        if (selectedMedia) {
            console.log(selectedMedia);
        }

        return (
            <Fragment>
                <SingleItemSelection
                    disabled={disabled}
                    emptyText={translate('sulu_media.select_media_singular')}
                    leftButton={{
                        icon: 'su-image',
                        onClick: this.handleOverlayOpen,
                    }}
                    onRemove={this.singleMediaSelectionStore.selectedMedia ? this.handleRemove : undefined}
                >
                    {selectedMedia &&
                    <MediaSelectionItem mimeType={selectedMedia.mimeType} thumbnail={selectedMedia.thumbnail}>
                        {selectedMedia.title}
                    </MediaSelectionItem>
                    }
                </SingleItemSelection>
                <MediaSelectionOverlay
                    excludedIds={value ? [value] : []}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                />
            </Fragment>
        );
    }
}
