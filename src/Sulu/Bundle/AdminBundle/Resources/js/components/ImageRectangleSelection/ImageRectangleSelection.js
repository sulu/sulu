// @flow
import {action, computed, observable} from 'mobx';
import log from 'loglevel';
import {observer} from 'mobx-react';
import React from 'react';
import RectangleSelection, {RoundingNormalizer} from '../RectangleSelection';
import type {SelectionData} from '../RectangleSelection';
import withContainerSize from '../withContainerSize';
import imageRectangleSelectionStyles from './imageRectangleSelection.scss';

type Props = {|
    containerHeight: number,
    containerWidth: number,
    image: string,
    minWidth?: number,
    minHeight?: number,
    onChange: (s: ?SelectionData) => void,
    value: ?SelectionData,
|};

@observer class ImageRectangleSelection extends React.Component<Props> {
    image: Image;
    rounding = new RoundingNormalizer();
    @observable imageLoaded = false;

    naturalHorizontalToScaled = (h: number) => h * this.imageResizedWidth / this.image.naturalWidth;
    scaledHorizontalToNatural = (h: number) => h * this.image.naturalWidth / this.imageResizedWidth;
    naturalVerticalToScaled = (v: number) => v * this.imageResizedHeight / this.image.naturalHeight;
    scaledVerticalToNatural = (v: number) => v * this.image.naturalHeight / this.imageResizedHeight;

    naturalDataToScaled(data: SelectionData): SelectionData {
        return {
            width: this.naturalHorizontalToScaled(data.width),
            height: this.naturalVerticalToScaled(data.height),
            left: this.naturalHorizontalToScaled(data.left),
            top: this.naturalVerticalToScaled(data.top),
        };
    }

    scaledDataToNatural(data: SelectionData): SelectionData {
        return {
            width: this.scaledHorizontalToNatural(data.width),
            height: this.scaledVerticalToNatural(data.height),
            left: this.scaledHorizontalToNatural(data.left),
            top: this.scaledVerticalToNatural(data.top),
        };
    }

    constructor(props: Props) {
        super(props);

        this.image = new Image();
        this.image.onload = action(() => this.imageLoaded = true);
        this.image.onerror = () => log.error('Failed to preload image "' + this.props.image + '"');
        this.image.src = this.props.image;
    }

    @computed get imageResizedHeight(): number {
        if (this.imageTouchesHorizontalBorders()) {
            return Math.min(this.image.naturalHeight, this.props.containerHeight);
        } else {
            return this.imageResizedWidth * this.image.naturalHeight / this.image.naturalWidth;
        }
    }

    @computed get imageResizedWidth(): number {
        if (this.imageTouchesHorizontalBorders()) {
            return this.imageResizedHeight * this.image.naturalWidth / this.image.naturalHeight;
        } else {
            return Math.min(this.image.naturalWidth, this.props.containerWidth);
        }
    }

    imageTouchesHorizontalBorders() {
        const imageHeightToWidth = this.image.naturalHeight / this.image.naturalWidth;
        const containerHeightToWidth = this.props.containerHeight / this.props.containerWidth;
        return imageHeightToWidth > containerHeightToWidth;
    }

    handleRectangleSelectionChange = (data: ?SelectionData) => {
        const {onChange} = this.props;
        onChange(data ? this.rounding.normalize(this.scaledDataToNatural(data)) : undefined);
    };

    render() {
        if (!this.imageLoaded || !this.props.containerWidth || !this.props.containerHeight) {
            return null;
        }

        const minWidth = this.props.minWidth ? this.naturalHorizontalToScaled(this.props.minWidth) : null;
        const minHeight = this.props.minHeight ? this.naturalVerticalToScaled(this.props.minHeight) : null;
        const value = this.props.value ? this.naturalDataToScaled(this.props.value) : undefined;

        return (
            <RectangleSelection
                minHeight={minHeight}
                minWidth={minWidth}
                onChange={this.handleRectangleSelectionChange}
                round={false}
                value={value}
            >
                <img
                    height={this.imageResizedHeight}
                    src={this.props.image}
                    width={this.imageResizedWidth}
                />
            </RectangleSelection>
        );
    }
}

export {
    ImageRectangleSelection,
};

export default withContainerSize(ImageRectangleSelection, imageRectangleSelectionStyles.container);
