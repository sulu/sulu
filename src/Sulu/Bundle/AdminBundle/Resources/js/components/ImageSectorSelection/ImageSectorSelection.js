// @flow
import {action, computed, observable} from 'mobx';
import DimensionsConverter from './DimensionsConverter';
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
        imageSrc: string,
    };

    image: Image;
    rounding = new RoundingNormalizer();

    @observable imageLoaded = false;
    @observable containerWidth: number;
    @observable containerHeight: number;

    @action componentWillMount() {
        this.image = new Image();
        this.image.onload = action(() => this.imageLoaded = true);
        this.image.onerror = () => log.error('Failed to preload image');
        this.image.src = this.props.imageSrc;
    }

    @computed get converter(): DimensionsConverter {
        return new DimensionsConverter(
            this.image.naturalWidth, this.image.naturalHeight, this.imageResizedWidth, this.imageResizedHeight);
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
        let imageHeightToWidth = this.image.naturalHeight / this.image.naturalWidth;
        let containerHeightToWidth = this.containerHeight / this.containerWidth;
        return imageHeightToWidth > containerHeightToWidth;
    }

    handleRectangleSelectionChange = (data: SelectionData) => {
        const computedData = this.rounding.normalize(this.converter.realDataToComputed(data));
        if (this.props.onChange) {
            this.props.onChange(computedData);
        }
    };

    readContainerDimensions = (container: HTMLElement) => {
        if (!container) return;
        window.requestAnimationFrame(action(() => {
            this.containerWidth = container.clientWidth;
            this.containerHeight = container.clientHeight;
        }));
    };

    render() {
        if (!this.imageLoaded || !this.containerWidth || !this.containerHeight) {
            return this.renderContainer();
        } else {
            return this.renderSelection();
        }
    }

    renderContainer(content: ?React.Element<*>) {
        return <div ref={this.readContainerDimensions} className={selectionStyles.selection}>{content}</div>;
    }

    renderSelection() {
        let minWidth, minHeight, initialSelection;
        if (this.props.minWidth) {
            minWidth = this.converter.computedHorizontalToReal(this.props.minWidth);
        }
        if (this.props.minHeight) {
            minHeight = this.converter.computedVerticalToReal(this.props.minHeight);
        }
        if (this.props.initialSelection) {
            initialSelection = this.converter.computedDataToReal(this.props.initialSelection);
        }
        return this.renderContainer(
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
                        src={this.props.imageSrc} />
                </RectangleSelection>
            </div>
        );
    }
}
