// @flow
import {action, computed, observable} from 'mobx';
import React from 'react';
import RectangleSelection from '../RectangleSelection';
import RoundingNormalizer from '../RectangleSelection/dataNormalizers/RoundingNormalizer';
import type {SelectionData} from '../RectangleSelection/types';
import log from 'loglevel';
import {observer} from 'mobx-react';
import selectionStyles from './imageRectangleSelection.scss';

@observer
export default class ImageRectangleSelection extends React.PureComponent {
    props: {
        /** Determines the position at which the selection box is rendered at the beginning. */
        initialSelection?: SelectionData,
        minWidth?: number,
        minHeight?: number,
        onChange?: (s: SelectionData) => void,
        src: string,
    };

    image: Image;
    rounding = new RoundingNormalizer();

    @observable imageLoaded = false;
    @observable containerWidth: number;
    @observable containerHeight: number;

    naturalHorizontalToReal = (h: number) => h * this.imageResizedWidth / this.image.naturalWidth;
    scaledHorizontalToNatural = (h: number) => h * this.image.naturalWidth / this.imageResizedWidth;
    naturalVerticalToReal = (v: number) => v * this.imageResizedHeight / this.image.naturalHeight;
    scaledVerticalToNatural = (v: number) => v * this.image.naturalHeight / this.imageResizedHeight;

    naturalDataToReal(data: SelectionData): SelectionData {
        return {
            width: this.naturalHorizontalToReal(data.width),
            height: this.naturalVerticalToReal(data.height),
            left: this.naturalHorizontalToReal(data.left),
            top: this.naturalVerticalToReal(data.top),
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

    componentWillMount() {
        this.image = new Image();
        this.image.onload = action(() => this.imageLoaded = true);
        this.image.onerror = () => log.error('Failed to preload image "' + this.props.src + '"');
        this.image.src = this.props.src;
    }

    @computed get imageResizedHeight(): number {
        if (this.imageTouchesHorizontalBorders()) {
            return Math.min(this.image.naturalHeight, this.containerHeight);
        } else {
            return this.imageResizedWidth * this.image.naturalHeight / this.image.naturalWidth;
        }
    }

    @computed get imageResizedWidth(): number {
        if (this.imageTouchesHorizontalBorders()) {
            return this.imageResizedHeight * this.image.naturalWidth / this.image.naturalHeight;
        } else {
            return Math.min(this.image.naturalWidth, this.containerWidth);
        }
    }

    imageTouchesHorizontalBorders() {
        const imageHeightToWidth = this.image.naturalHeight / this.image.naturalWidth;
        const containerHeightToWidth = this.containerHeight / this.containerWidth;
        return imageHeightToWidth > containerHeightToWidth;
    }

    handleRectangleSelectionChange = (data: SelectionData) => {
        if (this.props.onChange) {
            const onChange = this.props.onChange;
            onChange(this.rounding.normalize(this.scaledDataToNatural(data)));
        }
    };

    readContainerDimensions = (container: HTMLElement) => {
        if (!container) {
            return;
        }
        window.requestAnimationFrame(action(() => {
            this.containerWidth = container.clientWidth;
            this.containerHeight = container.clientHeight;
        }));
    };

    render() {
        let content;
        if (this.imageLoaded && this.containerWidth && this.containerHeight) {
            let minWidth, minHeight, initialSelection;
            if (this.props.minWidth) {
                minWidth = this.naturalHorizontalToReal(this.props.minWidth);
            }
            if (this.props.minHeight) {
                minHeight = this.naturalVerticalToReal(this.props.minHeight);
            }
            if (this.props.initialSelection) {
                initialSelection = this.naturalDataToReal(this.props.initialSelection);
            }
            content = (
                <RectangleSelection
                    initialSelection={initialSelection}
                    minWidth={minWidth}
                    minHeight={minHeight}
                    onChange={this.handleRectangleSelectionChange}
                    round={false}>
                    <img
                        width={this.imageResizedWidth}
                        height={this.imageResizedHeight}
                        src={this.props.src} />
                </RectangleSelection>
            );
        }

        return <div ref={this.readContainerDimensions} className={selectionStyles.selection}>{content}</div>;
    }
}
