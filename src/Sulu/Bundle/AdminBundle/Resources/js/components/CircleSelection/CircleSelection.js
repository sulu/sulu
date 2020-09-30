// @flow
import type {Node} from 'react';
import React from 'react';
import withContainerSize from '../withContainerSize';
import CircleSelectionRenderer from './CircleSelectionRenderer';
import type {SelectionData} from './types';
import circleSelectionStyles from './circleSelection.scss';

type Props = {
    children?: Node,
    disabled: boolean,
    label?: string,
    maxRadius?: number,
    minRadius?: number,
    onChange: (value: ?SelectionData) => void,
    resizable: boolean,
    round: boolean,
    skin: 'filled' | 'outlined',
    usePercentageValues: boolean,
    value: SelectionData | typeof undefined,
};

class CircleSelection extends React.Component<Props & {
    containerHeight: number,
    containerWidth: number,
}> {
    static defaultProps = {
        disabled: false,
        resizable: true,
        round: true,
        skin: 'outlined',
        usePercentageValues: false,
    };

    render() {
        const {
            children,
            containerHeight,
            containerWidth,
            disabled,
            label,
            maxRadius,
            minRadius,
            onChange,
            resizable,
            round,
            skin,
            usePercentageValues,
            value,
        } = this.props;

        return (
            <div className={circleSelectionStyles.selection}>
                {children}
                <CircleSelectionRenderer
                    containerHeight={containerHeight}
                    containerWidth={containerWidth}
                    disabled={disabled}
                    label={label}
                    maxRadius={maxRadius}
                    minRadius={minRadius}
                    onChange={onChange}
                    resizable={resizable}
                    round={round}
                    skin={skin}
                    usePercentageValues={usePercentageValues}
                    value={value}
                />
            </div>
        );
    }
}

export {
    CircleSelection,
};

const Component = withContainerSize(CircleSelection, circleSelectionStyles.container);

export default class CircleSelectionComponent extends React.Component<Props> {
    static defaultProps = CircleSelection.defaultProps;

    static Renderer = CircleSelectionRenderer;

    render() {
        return <Component {...this.props} />;
    }
}
