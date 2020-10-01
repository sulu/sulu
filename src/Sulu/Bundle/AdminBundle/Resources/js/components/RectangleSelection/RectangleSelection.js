// @flow
import React from 'react';
import type {Node} from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import withContainerSize from '../withContainerSize';
import type {Normalizer, RectangleChange, SelectionData} from './types';
import ModifiableRectangle from './ModifiableRectangle';
import PositionNormalizer from './normalizers/PositionNormalizer';
import RatioNormalizer from './normalizers/RatioNormalizer';
import RoundingNormalizer from './normalizers/RoundingNormalizer';
import SizeNormalizer from './normalizers/SizeNormalizer';
import withPercentageValues from './withPercentageValues';
import rectangleSelectionStyles from './rectangleSelection.scss';

type Props = {
    backdrop: boolean,
    children?: Node,
    containerHeight: number,
    containerWidth: number,
    disabled: boolean,
    label?: string,
    minHeight: number | typeof undefined,
    minSizeNotification: boolean,
    minWidth: number | typeof undefined,
    onChange: (s: ?SelectionData) => void,
    round: boolean,
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

@observer
class RectangleSelectionComponent extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        disabled: false,
        minHeight: undefined,
        minSizeNotification: true,
        minWidth: undefined,
        round: true,
        usePercentageValues: false,
    };

    @computed get value() {
        const {value} = this.props;

        if (!value) {
            return this.maximumSelection;
        }

        return value;
    }

    componentDidMount() {
        this.setInitialValue();
    }

    componentDidUpdate() {
        this.setInitialValue();
    }

    setInitialValue = () => {
        const {onChange, value} = this.props;

        if (!this.props.containerHeight || !this.props.containerWidth) {
            return;
        }

        if (!value) {
            onChange(this.value);
        }
    };

    static createNormalizers(props: Props): Array<Normalizer> {
        const {
            containerWidth,
            containerHeight,
            minWidth,
            minHeight,
            round,
        } = props;

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

        if (minWidth && minHeight) {
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

    @computed get normalizers() {
        return RectangleSelectionComponent.createNormalizers(this.props);
    }

    normalize(selection: SelectionData): SelectionData {
        return this.normalizers.reduce((data, normalizer) => normalizer.normalize(data), selection);
    }

    @computed get maximumSelection(): SelectionData {
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
    }

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

        onChange(this.maximumSelection);
    };

    handleRectangleChange = (change: RectangleChange) => {
        const {value} = this;
        const {onChange} = this.props;

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
        } = this.props;
        const {height, left, top, width} = this.value;

        let backdropSize = 0;
        if (backdrop && containerHeight && containerWidth) {
            backdropSize = Math.max(containerHeight, containerWidth);
        }

        let minSizeReached = false;
        if (minSizeNotification) {
            if (height <= (minHeight || 0) && width <= (minWidth || 0)) {
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

const RectangleSelectionWrapper = withPercentageValues(RectangleSelectionComponent);

class RectangleSelectionContainer extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        disabled: false,
        minHeight: undefined,
        minSizeNotification: true,
        minWidth: undefined,
        round: true,
        usePercentageValues: false,
    };

    render() {
        const {children, ...rest} = this.props;

        return (
            <div className={rectangleSelectionStyles.selection}>
                {children}
                <RectangleSelectionWrapper {...rest} />
            </div>
        );
    }
}

// This export should only be used in tests
export {RectangleSelectionContainer};

const RectangleSelectionContainerWrapper = withContainerSize(
    RectangleSelectionContainer,
    rectangleSelectionStyles.container
);

export default class RectangleSelection extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        containerHeight: 0,
        containerWidth: 0,
        disabled: false,
        minHeight: undefined,
        minSizeNotification: true,
        minWidth: undefined,
        round: true,
        usePercentageValues: false,
    };

    render() {
        const {children} = this.props;

        if (children) {
            return <RectangleSelectionContainerWrapper {...this.props} />;
        }

        return <RectangleSelectionWrapper {...this.props} />;
    }
}
