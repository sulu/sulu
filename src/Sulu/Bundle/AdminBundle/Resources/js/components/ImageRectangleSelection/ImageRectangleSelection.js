// @flow
import {action, computed, observable} from 'mobx';
import log from 'loglevel';
import {observer} from 'mobx-react';
import React from 'react';
import RectangleSelection from '../RectangleSelection';
import type {SelectionData} from '../RectangleSelection';
import withContainerSize from '../withContainerSize';
import imageRectangleSelectionStyles from './imageRectangleSelection.scss';

type Props = {|
    containerHeight: number,
    containerWidth: number,
    image: string,
    minHeight?: number,
    minWidth?: number,
    onChange: (s: ?SelectionData) => void,
    value: ?SelectionData,
|};

@observer
class ImageRectangleSelection extends React.Component<Props> {
    image: Image;
    @observable imageLoaded = false;

    naturalHorizontalToScaled = (h: number) => {
        return Math.max(h * this.scaledImageWidth / this.image.naturalWidth, 0);
    };
    scaledHorizontalToNatural = (h: number) => {
        return Math.min(h * this.image.naturalWidth / this.scaledImageWidth, this.image.naturalWidth);
    };
    naturalVerticalToScaled = (v: number) => {
        return Math.max(v * this.scaledImageHeight / this.image.naturalHeight, 0);
    };
    scaledVerticalToNatural = (v: number) => {
        return Math.min(v * this.image.naturalHeight / this.scaledImageHeight, this.image.naturalHeight);
    };

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

    @computed get scaledImageHeight(): number {
        if (this.imageFillsContainerHeight()) {
            return Math.min(this.image.naturalHeight, this.props.containerHeight);
        } else {
            return this.scaledImageWidth * this.image.naturalHeight / this.image.naturalWidth;
        }
    }

    @computed get scaledImageWidth(): number {
        if (this.imageFillsContainerHeight()) {
            return this.scaledImageHeight * this.image.naturalWidth / this.image.naturalHeight;
        } else {
            return Math.min(this.image.naturalWidth, this.props.containerWidth);
        }
    }

    imageFillsContainerHeight() {
        const imageHeightToWidth = this.image.naturalHeight / this.image.naturalWidth;
        const containerHeightToWidth = this.props.containerHeight / this.props.containerWidth;
        return imageHeightToWidth > containerHeightToWidth;
    }

    handleRectangleSelectionChange = (data: ?SelectionData) => {
        const {onChange} = this.props;
        onChange(data ? this.scaledDataToNatural(data) : undefined);
    };

    @computed get scaledMinDimensions() {
        const {minHeight, minWidth, containerHeight, containerWidth} = this.props;

        let height = minHeight ? this.naturalVerticalToScaled(minHeight) : undefined;
        let width = minWidth ? this.naturalHorizontalToScaled(minWidth) : undefined;

        if (height && height > containerHeight) {
            height = containerHeight;
            width = minWidth && minHeight ? height * minWidth / minHeight : undefined;
        }

        if (width && width > containerWidth) {
            width = containerWidth;
            height = minHeight && minWidth ? width * minHeight / minWidth : undefined;
        }

        return {width, height};
    }

    @computed get scaledMinWidth() {
        return this.scaledMinDimensions.width;
    }

    @computed get scaledMinHeight() {
        return this.scaledMinDimensions.height;
    }

    render() {
        if (!this.imageLoaded || !this.props.containerWidth || !this.props.containerHeight) {
            return null;
        }

        const value = this.props.value ? this.naturalDataToScaled(this.props.value) : undefined;

        return (
            <RectangleSelection
                minHeight={this.scaledMinHeight}
                minWidth={this.scaledMinWidth}
                onChange={this.handleRectangleSelectionChange}
                round={false}
                value={value}
            >
                <img
                    height={this.scaledImageHeight}
                    src={this.props.image}
                    width={this.scaledImageWidth}
                />
            </RectangleSelection>
        );
    }
}

export {
    ImageRectangleSelection,
};

export default withContainerSize(ImageRectangleSelection, imageRectangleSelectionStyles.container);
