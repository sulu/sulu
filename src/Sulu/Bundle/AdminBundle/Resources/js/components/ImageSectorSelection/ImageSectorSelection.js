// @flow
import {action, computed, observable} from 'mobx';
import React from 'react';
import RectangleSelection from '../RectangleSelection';
import RoundingNormalizer from '../RectangleSelection/dataNormalizers/RoundingNormalizer';
import type {SelectionData} from '../RectangleSelection/types';
import log from 'loglevel';
import {observer} from 'mobx-react';
import selectionStyles from './imageSectorSelection.scss';

@observer
export default class ImageSectorSelection extends React.PureComponent {
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
    realHorizontalToNatural = (h: number) => h * this.image.naturalWidth / this.imageResizedWidth;
    naturalVerticalToReal = (v: number) => v * this.imageResizedHeight / this.image.naturalHeight;
    realVerticalToNatural = (v: number) => v * this.image.naturalHeight / this.imageResizedHeight;

    naturalDataToReal(data: SelectionData): SelectionData {
        return {
            width: this.naturalHorizontalToReal(data.width),
            height: this.naturalVerticalToReal(data.height),
            left: this.naturalHorizontalToReal(data.left),
            top: this.naturalVerticalToReal(data.top),
        };
    }

    realDataToNatural(data: SelectionData): SelectionData {
        return {
            width: this.realHorizontalToNatural(data.width),
            height: this.realVerticalToNatural(data.height),
            left: this.realHorizontalToNatural(data.left),
            top: this.realVerticalToNatural(data.top),
        };
    }

    @action componentWillMount() {
        this.image = new Image();
        this.image.onload = action(() => this.imageLoaded = true);
        this.image.onerror = () => log.error('Failed to preload image ' + this.props.src);
        this.image.src = this.props.src;
    }

    @computed get imageResizedHeight(): number {
        if (this.imageTouchesHoriziontalBorders()) {
            return Math.min(this.image.naturalHeight, this.containerHeight);
        } else {
            return this.imageResizedWidth * this.image.naturalHeight / this.image.naturalWidth;
        }
    }

    @computed get imageResizedWidth(): number {
        if (this.imageTouchesHoriziontalBorders()) {
            return this.imageResizedHeight * this.image.naturalWidth / this.image.naturalHeight;
        } else {
            return Math.min(this.image.naturalWidth, this.containerWidth);
        }
    }

    imageTouchesHoriziontalBorders() {
        const imageHeightToWidth = this.image.naturalHeight / this.image.naturalWidth;
        const containerHeightToWidth = this.containerHeight / this.containerWidth;
        return imageHeightToWidth > containerHeightToWidth;
    }

    handleRectangleSelectionChange = (data: SelectionData) => {
        const computedData = this.rounding.normalize(this.realDataToNatural(data));
        if (this.props.onChange) {
            this.props.onChange(computedData);
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
                <div>
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
                </div>
            );
        }

        return <div ref={this.readContainerDimensions} className={selectionStyles.selection}>{content}</div>;
    }
}
