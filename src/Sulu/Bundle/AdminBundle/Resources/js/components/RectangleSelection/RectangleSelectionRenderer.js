// @flow
import React from 'react';
import type {Normalizer, RectangleChange, SelectionData} from './types';
import ModifiableRectangle from './ModifiableRectangle';
import PositionNormalizer from './normalizers/PositionNormalizer';
import RatioNormalizer from './normalizers/RatioNormalizer';
import RoundingNormalizer from './normalizers/RoundingNormalizer';
import SizeNormalizer from './normalizers/SizeNormalizer';

type Props = {
    backdrop: boolean,
    containerHeight: number,
    containerWidth: number,
    disabled: boolean,
    forceRatio: boolean,
    label?: string,
    minHeight?: number,
    minSizeNotification: boolean,
    minWidth?: number,
    onChange: (s: ?SelectionData) => void,
    round: boolean,
    value: SelectionData | typeof undefined,
};

export default class RectangleSelectionRenderer extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        disabled: false,
        forceRatio: true,
        minSizeNotification: true,
        round: true,
    };

    static createNormalizers(props: Props): Array<Normalizer> {
        const {containerWidth, containerHeight, minWidth, minHeight, forceRatio, round} = props;

        if (!containerWidth || !containerHeight) {
            return [];
        }

        let normalizers = [
            new SizeNormalizer(
                containerWidth,
                containerHeight,
                minWidth,
                minHeight
            ),
            new PositionNormalizer(
                containerWidth,
                containerHeight
            ),
        ];

        if (forceRatio && minWidth && minHeight) {
            normalizers = [
                ...normalizers,
                new RatioNormalizer(
                    containerWidth,
                    containerHeight,
                    minWidth,
                    minHeight
                ),
            ];
        }

        if (round) {
            normalizers = [
                ...normalizers,
                new RoundingNormalizer(),
            ];
        }

        return normalizers;
    }

    get normalizers() {
        return RectangleSelectionRenderer.createNormalizers(this.props);
    }

    normalize(selection: SelectionData): SelectionData {
        return this.normalizers.reduce((data, normalizer) => normalizer.normalize(data), selection);
    }

    getMaximumSelection = (): SelectionData => {
        const {containerWidth, containerHeight} = this.props;

        return this.normalize(
            this.centerSelection(
                this.normalize({
                    width: containerWidth,
                    height: containerHeight,
                    left: 0,
                    top: 0,
                })
            )
        );
    };

    centerSelection(selection: SelectionData): SelectionData {
        const {containerWidth, containerHeight} = this.props;

        if (selection.width < containerWidth) {
            selection.left = (containerWidth / 2) - (selection.width / 2);
        }

        if (selection.height < containerHeight) {
            selection.top = (containerHeight / 2) - (selection.height / 2);
        }

        return selection;
    }

    handleRectangleDoubleClick = () => {
        const {onChange} = this.props;

        onChange(this.getMaximumSelection());
    };

    handleRectangleChange = (change: RectangleChange) => {
        const {onChange, value = this.getMaximumSelection()} = this.props;

        onChange(this.normalize({
            left: value.left + change.left,
            top: value.top + change.top,
            height: value.height + change.height,
            width: value.width + change.width,
        }));
    };

    render() {
        const {
            backdrop,
            containerHeight,
            containerWidth,
            minHeight,
            minSizeNotification,
            minWidth,
            disabled,
            label,
            value = this.getMaximumSelection(),
        } = this.props;
        const {height, left, top, width} = value;

        let backdropSize = 0;
        if (backdrop && containerHeight && containerWidth) {
            backdropSize = Math.max(containerHeight, containerWidth);
        }

        let minSizeReached = false;
        if (minSizeNotification && height <= (minHeight || 0) && width <= (minWidth || 0)) {
            minSizeReached = true;
        }

        return (
            <ModifiableRectangle
                backdropSize={backdropSize}
                disabled={disabled}
                height={height}
                label={label}
                left={left}
                minSizeReached={minSizeReached}
                onChange={this.handleRectangleChange}
                onDoubleClick={this.handleRectangleDoubleClick}
                top={top}
                width={width}
            />
        );
    }
}
