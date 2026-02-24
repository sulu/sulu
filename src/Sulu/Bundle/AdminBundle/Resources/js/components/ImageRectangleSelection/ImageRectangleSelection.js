// @flow
import {action, computed, observable} from 'mobx';
import log from 'loglevel';
import {observer} from 'mobx-react';
import React from 'react';
import RectangleSelection from '../RectangleSelection';
import withContainerSize from '../withContainerSize';
import imageRectangleSelectionStyles from './imageRectangleSelection.scss';
import type {SelectionData} from '../RectangleSelection';

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

    normalizeNaturalSelection(data: SelectionData): SelectionData {
        const {naturalHeight, naturalWidth} = this.image;
        const width = Math.min(Math.max(data.width, 0), naturalWidth);
        const height = Math.min(Math.max(data.height, 0), naturalHeight);
        const left = Math.min(Math.max(data.left, 0), naturalWidth - width);
        const top = Math.min(Math.max(data.top, 0), naturalHeight - height);

        return {height, left, top, width};
    }

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
        const normalizedData = this.normalizeNaturalSelection(data);
        const left = this.naturalHorizontalToScaled(normalizedData.left);
        const top = this.naturalVerticalToScaled(normalizedData.top);
        const right = this.naturalHorizontalToScaled(normalizedData.left + normalizedData.width);
        const bottom = this.naturalVerticalToScaled(normalizedData.top + normalizedData.height);

        return {
            width: right - left,
            height: bottom - top,
            left,
            top,
        };
    }

    scaledDataToNatural(data: SelectionData): SelectionData {
        const left = this.scaledHorizontalToNatural(data.left);
        const top = this.scaledVerticalToNatural(data.top);
        const right = this.scaledHorizontalToNatural(data.left + data.width);
        const bottom = this.scaledVerticalToNatural(data.top + data.height);

        return this.normalizeNaturalSelection({
            width: right - left,
            height: bottom - top,
            left,
            top,
        });
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
