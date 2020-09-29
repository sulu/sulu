// @flow
import React from 'react';
import type {Normalizer, SelectionData} from './types';
import ModifiableCircle from './ModifiableCircle';
import PositionNormalizer from './normalizers/PositionNormalizer';
import RoundingNormalizer from './normalizers/RoundingNormalizer';
import SizeNormalizer from './normalizers/SizeNormalizer';

type Props = {
    containerHeight: number,
    containerWidth: number,
    disabled: boolean,
    filled: boolean,
    label?: string,
    maxRadius?: number,
    minRadius?: number,
    onChange: (value: ?SelectionData) => void,
    resizable: boolean,
    round: boolean,
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

export default class CircleSelectionRenderer extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        filled: false,
        resizable: true,
        round: true,
        usePercentageValues: false,
    };

    handleChange = (value: ?SelectionData) => {
        const {onChange, containerWidth, containerHeight, usePercentageValues} = this.props;

        if (!usePercentageValues || !value) {
            onChange(value);

            return;
        }

        const {left, top, radius = 0} = value;

        onChange({
            left: left / containerWidth,
            top: top / containerHeight,
            radius: radius / containerWidth,
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

        const {left, top, radius = 0} = value;

        return {
            left: left * containerWidth,
            top: top * containerHeight,
            radius: radius * containerWidth,
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
        const {containerWidth, containerHeight, maxRadius, minRadius, usePercentageValues, round, resizable} = props;

        if (!containerWidth || !containerHeight) {
            return [];
        }

        const normalizers = [
            new PositionNormalizer(
                containerWidth,
                containerHeight
            ),
        ];

        if (resizable) {
            const calculatedMaxRadius = usePercentageValues && maxRadius ? maxRadius * containerWidth : maxRadius;
            const calculatedMinRadius = usePercentageValues && minRadius ? minRadius * containerWidth : minRadius;

            normalizers.push(
                new SizeNormalizer(
                    containerWidth,
                    containerHeight,
                    calculatedMaxRadius,
                    calculatedMinRadius
                )
            );
        }

        if (round && !usePercentageValues) {
            normalizers.push(new RoundingNormalizer());
        }

        return normalizers;
    }

    get normalizers() {
        return CircleSelectionRenderer.createNormalizers(this.props);
    }

    normalize(selection: SelectionData): SelectionData {
        return this.normalizers.reduce((data, normalizer) => normalizer.normalize(data), selection);
    }

    getMaximumSelection = (): SelectionData => {
        const {containerWidth, containerHeight, resizable} = this.props;

        const radius = (containerWidth && containerHeight && resizable)
            ? Math.min(containerWidth, containerHeight) / 2
            : 0;

        return this.normalize(
            this.centerSelection({
                left: 0,
                top: 0,
                radius,
            })
        );
    };

    centerSelection(selection: SelectionData): SelectionData {
        const {containerWidth, containerHeight} = this.props;

        const halfWidth = containerWidth / 2;
        const halfHeight = containerHeight / 2;

        return {
            ...selection,
            left: halfWidth,
            top: halfHeight,
        };
    }

    handleCircleDoubleClick = () => {
        const {resizable} = this.props;

        if (resizable) {
            this.handleChange(this.getMaximumSelection());

            return;
        }

        this.handleChange(this.normalize(this.centerSelection(this.value)));
    };

    handleCircleChange = (value: SelectionData) => {
        this.handleChange(this.normalize(value));
    };

    render() {
        const {disabled, resizable, label, filled} = this.props;
        const {left, top, radius = 0} = this.value;

        return (
            <ModifiableCircle
                disabled={disabled}
                filled={filled}
                label={label}
                left={left}
                onChange={this.handleCircleChange}
                onDoubleClick={this.handleCircleDoubleClick}
                radius={radius}
                resizable={resizable}
                top={top}
            />
        );
    }
}
