// @flow
import React, {Fragment} from 'react';
import {action, computed, observable} from 'mobx';
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
    @observable changedFormatCroppings: Map<string, Object> = new Map();
    @observable dirty: boolean;
    mediaFormatStore: MediaFormatStore;

    constructor(props: Props) {
        super(props);

        const {id, locale} = this.props;

        this.mediaFormatStore = new MediaFormatStore(id, locale);
    }

    @computed get currentSelection() {
        const {formatKey} = this;

        if (!formatKey) {
            return undefined;
        }

        if (this.changedFormatCroppings.has(formatKey)) {
            return this.changedFormatCroppings.get(formatKey);
        }

        return this.convertFormatOptionsToSelection(
            this.mediaFormatStore.getFormatOptions(formatKey)
        );
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
            this.formatKey = this.availableFormats.length > 0 ? this.availableFormats[0].key : undefined;
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

    @action handleClose = () => {
        this.props.onClose();
        this.changedFormatCroppings.clear();
    };

    handleConfirm = () => {
        const {onConfirm} = this.props;

        const formatOptions = {};
        this.changedFormatCroppings.forEach((formatOption, formatKey) => {
            formatOptions[formatKey] = this.convertSelectionToFormatOptions(formatOption);
        });

        this.mediaFormatStore.updateFormatOptions(formatOptions).then(action(() => {
            onConfirm();
            this.changedFormatCroppings.clear();
        }));
    };

    @action handleFormatChange = (formatKey: string) => {
        this.formatKey = formatKey;
    };

    @action handleSelectionChange = (currentSelection: Object) => {
        const {formatKey} = this;

        if (!formatKey) {
            throw new Error(
                'It is not possible to change the selection without a selected format. '
                + 'This should not happen and is likely a bug.'
            );
        }

        this.changedFormatCroppings.set(formatKey, currentSelection);
    };

    render() {
        const {availableFormats, mediaFormatStore, selectedFormat} = this;
        const {image, open} = this.props;

        return (
            <Overlay
                confirmDisabled={this.changedFormatCroppings.size <= 0}
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
                                        {format.title +
                                            (mediaFormatStore.getFormatOptions(format.key)
                                                ? ' (' + translate('sulu_media.cropped') + ')'
                                                : ''
                                            )
                                        }
                                    </SingleSelect.Option>
                                ))}
                            </SingleSelect>
                        </div>
                        {selectedFormat && !mediaFormatStore.loading &&
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
