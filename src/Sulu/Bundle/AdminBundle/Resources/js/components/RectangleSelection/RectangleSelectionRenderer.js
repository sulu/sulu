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
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

export default class RectangleSelectionRenderer extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        disabled: false,
        forceRatio: true,
        minSizeNotification: true,
        round: true,
        usePercentageValues: false,
    };

    handleChange = (value: ?SelectionData) => {
        const {onChange, containerWidth, containerHeight, usePercentageValues} = this.props;

        if (!usePercentageValues || !value) {
            onChange(value);

            return;
        }

        const {left, top, width, height} = value;

        onChange({
            left: left / containerWidth,
            top: top / containerHeight,
            width: width / containerWidth,
            height: height / containerHeight,
        });
    };

    get value() {
        const {value, containerWidth, containerHeight, usePercentageValues} = this.props;

        if (!value) {
            return this.getMaximumSelection();
        }

        if (!usePercentageValues) {
            return value;
        }

        const {left, top, width, height} = value;

        return {
            left: left * containerWidth,
            top: top * containerHeight,
            width: width * containerWidth,
            height: height * containerHeight,
        };
    }

    componentDidMount() {
        this.setInitialValue();
    }

    componentDidUpdate() {
        this.setInitialValue();
    }

    setInitialValue = () => {
        const {value} = this.props;

        if (!value) {
            this.handleChange(this.value);
        }
    };

    static createNormalizers(props: Props): Array<Normalizer> {
        const {
            containerWidth,
            containerHeight,
            minWidth = 0,
            minHeight = 0,
            forceRatio,
            usePercentageValues,
            round,
        } = props;

        if (!containerWidth || !containerHeight) {
            return [];
        }

        const calculatedMinWidth = usePercentageValues && minWidth ? minWidth * containerWidth : minWidth;
        const calculatedMinHeight = usePercentageValues && minHeight ? minHeight * containerHeight : minHeight;

        let normalizers = [
            new SizeNormalizer(
                containerWidth,
                containerHeight,
                calculatedMinWidth,
                calculatedMinHeight
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
                    calculatedMinWidth,
                    calculatedMinHeight
                ),
            ];
        }

        if (round && !usePercentageValues) {
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
        this.handleChange(this.getMaximumSelection());
    };

    handleRectangleChange = (change: RectangleChange) => {
        const {value} = this;

        this.handleChange(this.normalize({
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
            usePercentageValues,
        } = this.props;
        const {height, left, top, width} = this.value;

        let backdropSize = 0;
        if (backdrop && containerHeight && containerWidth) {
            backdropSize = Math.max(containerHeight, containerWidth);
        }

        let minSizeReached = false;
        if (minSizeNotification) {
            const calculatedMinWidth = usePercentageValues && minWidth ? minWidth * containerWidth : minWidth;
            const calculatedMinHeight = usePercentageValues && minHeight ? minHeight * containerHeight : minHeight;

            if (height <= (calculatedMinHeight || 0) && width <= (calculatedMinWidth || 0)) {
                minSizeReached = true;
            }
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
