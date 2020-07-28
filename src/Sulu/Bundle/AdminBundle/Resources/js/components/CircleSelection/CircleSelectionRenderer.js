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
    value: SelectionData | typeof undefined,
};

export default class CircleSelectionRenderer extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        filled: false,
        resizable: true,
        round: true,
    };

    static createNormalizers(props: Props): Array<Normalizer> {
        const {containerWidth, containerHeight, maxRadius, minRadius, round, resizable} = props;

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
            normalizers.push(
                new SizeNormalizer(
                    containerWidth,
                    containerHeight,
                    maxRadius,
                    minRadius
                )
            );
        }

        if (round) {
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

        let radius;
        if (containerWidth && containerHeight && resizable) {
            radius = Math.min(containerWidth, containerHeight) / 2;
        } else {
            radius = 0;
        }

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
        const {onChange, resizable, value} = this.props;

        if (resizable || !value) {
            onChange(this.getMaximumSelection());

            return;
        }

        onChange(this.normalize(this.centerSelection(value)));
    };

    handleCircleChange = (value: SelectionData) => {
        const {onChange} = this.props;

        onChange(this.normalize(value));
    };

    render() {
        const {disabled, resizable, label, filled, value = this.getMaximumSelection()} = this.props;
        const {left, top, radius = 0} = value;

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
