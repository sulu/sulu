// @flow
import type {Node} from 'react';
import {observer} from 'mobx-react';
import React from 'react';
import withContainerSize from '../withContainerSize';
import type {Normalizer, RectangleChange, SelectionData} from './types';
import ModifiableRectangle from './ModifiableRectangle';
import PositionNormalizer from './normalizers/PositionNormalizer';
import RatioNormalizer from './normalizers/RatioNormalizer';
import RoundingNormalizer from './normalizers/RoundingNormalizer';
import SizeNormalizer from './normalizers/SizeNormalizer';
import rectangleSelectionStyles from './rectangleSelection.scss';

type Props = {
    children?: Node,
    containerHeight: number,
    containerWidth: number,
    minWidth?: number,
    minHeight?: number,
    onChange: (s: SelectionData) => void,
    round: boolean,
    value: SelectionData | typeof undefined,
};

@observer
export class RectangleSelection extends React.Component<Props> {
    static defaultProps = {
        round: true,
    };

    normalizers: Array<Normalizer> = [];

    static createNormalizers(props: Props): Array<Normalizer> {
        if (!props.containerWidth || !props.containerHeight) {
            return [];
        }

        const normalizers = [];
        normalizers.push(new SizeNormalizer(
            props.containerWidth,
            props.containerHeight,
            props.minWidth,
            props.minHeight
        ));
        normalizers.push(new PositionNormalizer(props.containerWidth, props.containerHeight));
        if (props.minWidth && props.minHeight) {
            normalizers.push(new RatioNormalizer(
                props.containerWidth,
                props.containerHeight,
                props.minWidth,
                props.minHeight
            ));
        }

        if (props.round) {
            normalizers.push(new RoundingNormalizer());
        }

        return normalizers;
    }

    constructor(props: Props) {
        super(props);

        this.normalizers = RectangleSelection.createNormalizers(this.props);
    }

    componentWillReceiveProps(nextProps: Props) {
        this.normalizers = RectangleSelection.createNormalizers(nextProps);
    }

    normalize(selection: SelectionData): SelectionData {
        return this.normalizers.reduce((data, normalizer) => normalizer.normalize(data), selection);
    }

    getMaximumSelection = (): SelectionData => {
        const {containerWidth, containerHeight} = this.props;

        return this.centerSelection(this.normalize({
            width: containerWidth,
            height: containerHeight,
            left: 0,
            top: 0,
        }));
    };

    centerSelection(selection: SelectionData): SelectionData {
        if (selection.width < this.props.containerWidth) {
            selection.left = (this.props.containerWidth / 2) - (selection.width / 2);
        }
        if (selection.height < this.props.containerHeight) {
            selection.top = (this.props.containerHeight / 2) - (selection.height / 2);
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
        const {containerHeight, containerWidth, value = this.getMaximumSelection()} = this.props;
        const {height, left, top, width} = value;

        let backdropSize = 0;

        if (containerHeight && containerWidth) {
            backdropSize = Math.max(containerHeight, containerWidth);
        }

        return (
            <div className={rectangleSelectionStyles.selection}>
                {this.props.children}
                <ModifiableRectangle
                    backdropSize={backdropSize}
                    height={height}
                    left={left}
                    onChange={this.handleRectangleChange}
                    onDoubleClick={this.handleRectangleDoubleClick}
                    top={top}
                    width={width}
                />
            </div>
        );
    }
}

export default withContainerSize(RectangleSelection, rectangleSelectionStyles.container);
