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
    @observable formats: ?Array<Object>;
    @observable formatKey: ?string;

    @computed get format() {
        if (!this.formats) {
            throw new Error('Cannot access format as long as formats have not finished loading!');
        }

        const format = this.formats.find((format) => format.key === this.formatKey);

        if (!format) {
            throw new Error(
                'Format with key "' + (this.formatKey || 'undefined') + '" does not exist! '
                + 'This should not happen and is likely a bug.'
            );
        }

        return format;
    }

    componentDidMount() {
        formatStore.loadFormats().then(action((formats) => {
            this.formats = formats;
            this.formatKey = this.formats.length > 0 ? this.formats[0].key : undefined;
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
        const {formats} = this;
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
                {formats
                    ? <div className={cropOverlayStyles.cropOverlayContainer}>
                        <div className={cropOverlayStyles.formatSelect}>
                            <SingleSelect onChange={this.handleFormatChange} value={this.formatKey}>
                                {formats.filter((format) => !format.internal).map((format) => (
                                    <SingleSelect.Option key={format.key} value={format.key}>
                                        {format.title}
                                    </SingleSelect.Option>
                                ))}
                            </SingleSelect>
                        </div>
                        <ImageRectangleSelection
                            image={image}
                            minHeight={this.format.scale.y}
                            minWidth={this.format.scale.x}
                        />
                    </div>
                    : <Loader />
                }
            </Overlay>
        );
    }
}
