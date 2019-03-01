// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {ImageRectangleSelection, Loader, Overlay, SingleSelect} from 'sulu-admin-bundle/components';
import {translate} from 'sulu-admin-bundle/utils';
import formatStore from '../../stores/FormatStore';
import cropOverlayStyles from './cropOverlay.scss';

type Props = {|
    image: string,
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
|};

@observer
export default class CropOverlay extends React.Component<Props> {
    @observable rawFormats: ?Array<Object>;
    @observable formatKey: ?string;

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

    handleClose = () => {
        this.props.onClose();
    };

    handleConfirm = () => {
        this.props.onConfirm();
    };

    @action handleFormatChange = (formatKey: string) => {
        this.formatKey = formatKey;
    };

    render() {
        const {availableFormats, selectedFormat} = this;
        const {image, open} = this.props;

        return (
            <Overlay
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
                        {selectedFormat && <ImageRectangleSelection
                            image={image}
                            minHeight={selectedFormat.scale.y}
                            minWidth={selectedFormat.scale.x}
                        />}
                    </div>
                    : <Loader />
                }
            </Overlay>
        );
    }
}
