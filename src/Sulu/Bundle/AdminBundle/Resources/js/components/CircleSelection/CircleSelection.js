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
    filled: boolean,
    label?: string,
    maxRadius?: number,
    minRadius?: number,
    onChange: (value: ?SelectionData) => void,
    resizable: boolean,
    round: boolean,
    value: SelectionData | typeof undefined,
};

class CircleSelection extends React.Component<Props & {
    containerHeight: number,
    containerWidth: number,
}> {
    static defaultProps = {
        disabled: false,
        filled: false,
        resizable: true,
        round: true,
    };

    render() {
        const {
            children,
            containerHeight,
            containerWidth,
            disabled,
            filled,
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
                    filled={filled}
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

export default class CircleSelectionComponent extends React.Component<Props> {
    static defaultProps = CircleSelection.defaultProps;

    static Renderer = CircleSelectionRenderer;

    render() {
        const Component = withContainerSize(CircleSelection, circleSelectionStyles.container);

        return <Component {...this.props} />;
    }
}
