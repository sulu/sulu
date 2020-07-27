// @flow
import type {Node} from 'react';
import React from 'react';
import withContainerSize from '../withContainerSize';
import CircleSelectionRenderer from './CircleSelectionRenderer';
import type {SelectionData} from './types';
import circleSelectionStyles from './circleSelection.scss';

type Props = {
    children?: Node,
    containerHeight: number,
    containerWidth: number,
    disabled: boolean,
    label?: string,
    maxRadius?: number,
    minRadius?: number,
    onChange: (value: ?SelectionData) => void,
    resizable: boolean,
    round: boolean,
    value: SelectionData | typeof undefined,
};

class CircleSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        resizable: true,
        round: true,
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
                    value={value}
                />
            </div>
        );
    }
}

export {
    CircleSelection,
};

const CircleSelectionComponent = withContainerSize(CircleSelection, circleSelectionStyles.container);
CircleSelectionComponent.Renderer = CircleSelectionRenderer;

export default CircleSelectionComponent;
