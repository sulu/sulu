// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import type {IObservableValue} from 'mobx';
import {action, observable, reaction, toJS} from 'mobx';
import SingleItemSelection from 'sulu-admin-bundle/components/SingleItemSelection';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import SingleSelectionStore from 'sulu-admin-bundle/stores/SingleSelectionStore';
import {getIconForDisplayOption, getTranslationForDisplayOption} from '../../utils/MediaSelectionHelper';
import SingleMediaSelectionOverlay from '../SingleMediaSelectionOverlay';
import MimeTypeIndicator from '../../components/MimeTypeIndicator';
import type {DisplayOption, Media} from '../../types';
import type {Value} from './types';
import singleMediaSelectionStyle from './singleMediaSelection.scss';

type Props = {|
    disabled: boolean,
    displayOptions: Array<DisplayOption>,
    locale: IObservableValue<string>,
    onChange: (selectedId: Value, media: ?Media) => void,
    types: Array<string>,
    valid: boolean,
    value: Value,
|}

const MEDIA_RESOURCE_KEY = 'media';
const THUMBNAIL_SIZE = 'sulu-25x25';

@observer
class SingleMediaSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        displayOptions: [],
        types: [],
        valid: true,
        value: {displayOption: undefined, id: undefined},
    };

    singleMediaSelectionStore: SingleSelectionStore<number, Media>;
    changeDisposer: () => *;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {locale, value} = this.props;

        this.singleMediaSelectionStore = new SingleSelectionStore(MEDIA_RESOURCE_KEY, value.id, locale);
        this.changeDisposer = reaction(
            () => (this.singleMediaSelectionStore.item ? this.singleMediaSelectionStore.item.id : undefined),
            (loadedMediaId: ?number) => {
                const {onChange, value} = this.props;

                if (value.id !== loadedMediaId) {
                    onChange({...value, id: loadedMediaId}, this.singleMediaSelectionStore.item);
                }
            }
        );
    }

    componentDidUpdate() {
        const newId = toJS(this.props.value.id);
        const loadedId = this.singleMediaSelectionStore.item ? this.singleMediaSelectionStore.item.id : undefined;

        if (loadedId !== newId) {
            this.singleMediaSelectionStore.loadItem(newId);
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

    handleDisplayOptionClick = (displayOption: ?DisplayOption) => {
        const {onChange, value} = this.props;

        onChange({...value, displayOption});
    };

    render() {
        const {disabled, displayOptions, locale, types, valid, value} = this.props;
        const {loading, item: media} = this.singleMediaSelectionStore;

        const rightButton = displayOptions.length > 0
            ? {
                icon: getIconForDisplayOption(value.displayOption),
                onClick: this.handleDisplayOptionClick,
                options: displayOptions.map((displayOption) => ({
                    icon: getIconForDisplayOption(displayOption),
                    label: getTranslationForDisplayOption(displayOption),
                    value: displayOption,
                })),
            }
            : undefined;

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
                    onRemove={media ? this.handleRemove : undefined}
                    rightButton={rightButton}
                    valid={valid}
                >
                    {media &&
                        <div className={singleMediaSelectionStyle.mediaItem}>
                            {media.thumbnails && media.thumbnails[THUMBNAIL_SIZE]
                                ? <img
                                    alt={media.title}
                                    className={singleMediaSelectionStyle.thumbnailImage}
                                    src={media.thumbnails[THUMBNAIL_SIZE]}
                                />
                                : <MimeTypeIndicator
                                    height={19}
                                    iconSize={14}
                                    mimeType={media.mimeType}
                                    width={19}
                                />
                            }
                            <div className={singleMediaSelectionStyle.mediaTitle}>{media.title}</div>
                        </div>
                    }
                </SingleItemSelection>
                <SingleMediaSelectionOverlay
                    excludedIds={media ? [media.id] : []}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    types={types}
                />
            </Fragment>
        );
    }
}

export default SingleMediaSelection;
