// @flow
import React, {Fragment} from 'react';
import {action, computed, observable, when} from 'mobx';
import {observer} from 'mobx-react';
import {ImageRectangleSelection, Loader, Overlay, SingleSelect} from 'sulu-admin-bundle/components';
import type {SelectionData} from 'sulu-admin-bundle/types';
import {translate} from 'sulu-admin-bundle/utils';
import MediaFormatStore from '../../stores/MediaFormatStore';
import type {MediaFormat} from '../../stores/MediaFormatStore';
import formatStore from '../../stores/FormatStore';
import cropOverlayStyles from './cropOverlay.scss';

type Props = {|
    id: string | number,
    image: string,
    locale: string,
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
|};

@observer
export default class CropOverlay extends React.Component<Props> {
    @observable rawFormats: ?Array<Object>;
    @observable formatKey: ?string;
    @observable currentSelection: ?Object;
    @observable dirty: boolean;
    mediaFormatStore: MediaFormatStore;

    constructor(props: Props) {
        super(props);

        const {id, locale} = this.props;

        this.mediaFormatStore = new MediaFormatStore(id, locale);
    }

    @computed get availableFormats(): Array<Object> {
        if (!this.rawFormats) {
            return [];
        }

        return this.rawFormats.filter((format) => !format.internal);
    }

    @computed get selectedFormat() {
        if (!this.availableFormats) {
            throw new Error('Cannot access format as long as formats have not finished loading!');
        }

        const format = this.availableFormats.find((format) => format.key === this.formatKey);

        if (!format) {
            return undefined;
        }

        return format;
    }

    componentDidMount() {
        formatStore.loadFormats().then(action((formats) => {
            this.rawFormats = formats;
            const formatKey = this.availableFormats.length > 0 ? this.availableFormats[0].key : undefined;
            this.formatKey = formatKey;

            if (formatKey) {
                when(
                    () => !this.mediaFormatStore.loading,
                    (): void => {
                        this.currentSelection = this.convertFormatOptionsToSelection(
                            this.mediaFormatStore.getFormatOptions(formatKey)
                        );
                    }
                );
            }
        }));
    }

    convertSelectionToFormatOptions(selection: SelectionData) {
        return {
            cropX: selection.left,
            cropY: selection.top,
            cropWidth: selection.width,
            cropHeight: selection.height,
        };
    }

    convertFormatOptionsToSelection(formatOption: ?MediaFormat) {
        if (!formatOption) {
            return undefined;
        }

        return {
            left: formatOption.cropX,
            top: formatOption.cropY,
            width: formatOption.cropWidth,
            height: formatOption.cropHeight,
        };
    }

    handleClose = () => {
        this.props.onClose();
    };

    handleConfirm = () => {
        const {currentSelection, selectedFormat} = this;
        const {onClose, onConfirm} = this.props;

        if (!selectedFormat) {
            throw new Error('Saving croppings is not possible without a format');
        }

        if (currentSelection) {
            this.mediaFormatStore.updateFormatOptions(
                selectedFormat.key,
                this.convertSelectionToFormatOptions(currentSelection)
            ).then(action(() => {
                onConfirm();
                this.dirty = false;
            }));
        } else {
            onClose();
        }
    };

    @action handleFormatChange = (formatKey: string) => {
        this.formatKey = formatKey;
        const formatOptions = this.mediaFormatStore.getFormatOptions(formatKey);

        this.currentSelection = formatOptions ? this.convertFormatOptionsToSelection(formatOptions) : undefined;
        this.dirty = false;
    };

    @action handleSelectionChange = (currentSelection: Object) => {
        this.currentSelection = currentSelection;
        this.dirty = true;
    };

    render() {
        const {availableFormats, selectedFormat} = this;
        const {image, open} = this.props;

        return (
            <Overlay
                confirmDisabled={!this.dirty}
                confirmLoading={this.mediaFormatStore.saving}
                confirmText={translate('sulu_admin.save')}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="large"
                title={translate('sulu_media.crop')}
            >
                {availableFormats
                    ? <div className={cropOverlayStyles.cropOverlayContainer}>
                        <div className={cropOverlayStyles.formatSelect}>
                            <SingleSelect onChange={this.handleFormatChange} value={this.formatKey}>
                                {availableFormats.map((format) => (
                                    <SingleSelect.Option key={format.key} value={format.key}>
                                        {format.title}
                                    </SingleSelect.Option>
                                ))}
                            </SingleSelect>
                        </div>
                        {selectedFormat && !this.mediaFormatStore.loading &&
                            <Fragment>
                                <ImageRectangleSelection
                                    image={image}
                                    minHeight={selectedFormat.scale.y}
                                    minWidth={selectedFormat.scale.x}
                                    onChange={this.handleSelectionChange}
                                    value={this.currentSelection}
                                />
                                <p>({translate('sulu_media.double_click_crop_and_maximize')})</p>
                            </Fragment>
                        }
                    </div>
                    : <Loader />
                }
            </Overlay>
        );
    }
}
