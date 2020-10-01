// @flow
import React from 'react';
import type {Node} from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import withContainerSize from '../withContainerSize';
import type {Normalizer, SelectionData} from './types';
import ModifiableCircle from './ModifiableCircle';
import PositionNormalizer from './normalizers/PositionNormalizer';
import RoundingNormalizer from './normalizers/RoundingNormalizer';
import SizeNormalizer from './normalizers/SizeNormalizer';
import withPercentageValues from './withPercentageValues';
import circleSelectionStyles from './circleSelection.scss';

type Props = {
    children?: Node,
    containerHeight: number,
    containerWidth: number,
    disabled: boolean,
    label?: string,
    maxRadius: number | typeof undefined,
    minRadius: number | typeof undefined,
    onChange: (value: ?SelectionData) => void,
    resizable: boolean,
    round: boolean,
    skin: 'filled' | 'outlined',
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

@observer
class CircleSelectionComponent extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        maxRadius: undefined,
        minRadius: undefined,
        resizable: true,
        round: true,
        skin: 'outlined',
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

    @computed get normalizers() {
        return CircleSelectionComponent.createNormalizers(this.props);
    }

    normalize(selection: SelectionData): SelectionData {
        return this.normalizers.reduce((data, normalizer) => normalizer.normalize(data), selection);
    }

    @computed get maximumSelection(): SelectionData {
        const {containerWidth, containerHeight, resizable, value} = this.props;

        const radius = resizable
            ? Math.min(containerWidth, containerHeight) / 2
            : (value && value.radius) || 0;

        return this.normalize(
            this.centerSelection({
                left: 0,
                top: 0,
                radius,
            })
        );
    }

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
        const {onChange, resizable} = this.props;

        if (resizable) {
            onChange(this.maximumSelection);

            return;
        }

        onChange(this.normalize(this.centerSelection(this.value)));
    };

    handleCircleChange = (value: SelectionData) => {
        const {onChange} = this.props;

        onChange(this.normalize(value));
    };

    render() {
        const {disabled, label, resizable, skin} = this.props;
        const {left, top, radius} = this.value;

        return (
            <ModifiableCircle
                disabled={disabled}
                label={label}
                left={left}
                onChange={this.handleCircleChange}
                onDoubleClick={this.handleCircleDoubleClick}
                radius={radius}
                resizable={resizable}
                skin={skin}
                top={top}
            />
        );
    }
}

const CircleSelectionWrapper = withPercentageValues(CircleSelectionComponent);

class CircleSelectionContainer extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        maxRadius: undefined,
        minRadius: undefined,
        resizable: true,
        round: true,
        skin: 'outlined',
        usePercentageValues: false,
    };

    render() {
        const {children, ...rest} = this.props;

        return (
            <div className={circleSelectionStyles.selection}>
                {children}
                <CircleSelectionWrapper {...rest} />
            </div>
        );
    }
}

// This export should only be used in tests
export {CircleSelectionContainer};

const CircleSelectionContainerWrapper = withContainerSize(
    CircleSelectionContainer,
    circleSelectionStyles.container
);

export default class CircleSelection extends React.Component<Props> {
    static defaultProps = {
        containerHeight: 0,
        containerWidth: 0,
        disabled: false,
        maxRadius: undefined,
        minRadius: undefined,
        resizable: true,
        round: true,
        skin: 'outlined',
        usePercentageValues: false,
    };

    render() {
        const {children} = this.props;

        if (children) {
            return <CircleSelectionContainerWrapper {...this.props} />;
        }

        return <CircleSelectionWrapper {...this.props} />;
    }
}
