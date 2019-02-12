// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import type {IObservableValue} from 'mobx';
import {action, observable, reaction, toJS} from 'mobx';
import SingleItemSelection from 'sulu-admin-bundle/components/SingleItemSelection';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import SingleMediaSelectionStore from '../../stores/SingleMediaSelectionStore';
import SingleMediaSelectionOverlay from '../SingleMediaSelectionOverlay';
import MimeTypeIndicator from '../../components/MimeTypeIndicator';
import type {Value} from './types';
import singleMediaSelectionStyle from './singleMediaSelection.scss';

type Props = {|
    disabled: boolean,
    locale: IObservableValue<string>,
    onChange: (selectedIds: Value) => void,
    valid: boolean,
    value: Value,
|}

const THUMBNAIL_SIZE = 'sulu-25x25';

@observer
export default class SingleMediaSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        valid: true,
        value: {id: undefined},
    };

    singleMediaSelectionStore: SingleMediaSelectionStore;
    changeDisposer: () => *;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {locale, value} = this.props;

        this.singleMediaSelectionStore = new SingleMediaSelectionStore(value.id, locale);
        this.changeDisposer = reaction(
            () => (this.singleMediaSelectionStore.selectedMediaId),
            (loadedMediaId: ?number) => {
                const {onChange, value} = this.props;

                if (value.id !== loadedMediaId) {
                    onChange({id: loadedMediaId});
                }
            }
        );
    }

    componentDidUpdate() {
        const {
            locale,
            value,
        } = this.props;

        const newSelectedId = toJS(value.id);
        const loadedSelectedId = toJS(this.singleMediaSelectionStore.selectedMediaId);

        if (loadedSelectedId !== newSelectedId) {
            this.singleMediaSelectionStore.loadSelectedMedia(newSelectedId, locale);
        }
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    handleRemove = () => {
        this.singleMediaSelectionStore.clear();
    };

    handleOverlayOpen = () => {
        this.openOverlay();
    };

    handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleOverlayConfirm = (selectedMedia: Object) => {
        this.singleMediaSelectionStore.set(selectedMedia);
        this.closeOverlay();
    };

    render() {
        const {
            disabled,
            locale,
            valid,
        } = this.props;
        const {
            loading,
            selectedMedia,
            selectedMediaId,
        } = this.singleMediaSelectionStore;

        return (
            <Fragment>
                <SingleItemSelection
                    disabled={disabled}
                    emptyText={translate('sulu_media.select_media_singular')}
                    leftButton={{
                        icon: 'su-image',
                        onClick: this.handleOverlayOpen,
                    }}
                    loading={loading}
                    onRemove={selectedMedia ? this.handleRemove : undefined}
                    valid={valid}
                >
                    {selectedMedia &&
                        <div className={singleMediaSelectionStyle.mediaItem}>
                            {selectedMedia.thumbnails[THUMBNAIL_SIZE]
                                ? <img
                                    alt={selectedMedia.title}
                                    className={singleMediaSelectionStyle.thumbnailImage}
                                    src={selectedMedia.thumbnails[THUMBNAIL_SIZE]}
                                />
                                : <MimeTypeIndicator
                                    height={19}
                                    iconSize={14}
                                    mimeType={selectedMedia.mimeType}
                                    width={19}
                                />
                            }
                            <div className={singleMediaSelectionStyle.mediaTitle}>{selectedMedia.title}</div>
                        </div>
                    }
                </SingleItemSelection>
                <SingleMediaSelectionOverlay
                    excludedIds={selectedMediaId ? [selectedMediaId] : []}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                />
            </Fragment>
        );
    }
}
