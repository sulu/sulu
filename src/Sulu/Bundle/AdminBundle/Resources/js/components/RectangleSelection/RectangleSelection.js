// @flow
import {action, observable} from 'mobx';
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
    /** Determines the position at which the selection box is rendered at the beginning */
    initialSelection?: SelectionData,
    minWidth?: number,
    minHeight?: number,
    /** Determines whether or not the data gets rounded */
    round: boolean,
    onChange?: (s: SelectionData) => void,
    children?: Node,
    containerHeight: number,
    containerWidth: number,
};

@observer
export class RectangleSelection extends React.Component<Props> {
    static defaultProps = {
        round: true,
    };

    @observable selection: SelectionData = {top: 0, left: 0, width: 0, height: 0};
    normalizers: Array<Normalizer> = [];

    static selectionsAreEqual(selection1: SelectionData, selection2: SelectionData) {
        return selection1.width === selection2.width
            && selection1.height === selection2.height
            && selection1.top === selection2.top
            && selection1.left === selection2.left;
    }

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

    containerDidMount = () => {
        this.setInitialSelection();
    };

    componentWillUpdate = () => {
        this.setSelection(this.selection);
    };

    setInitialSelection = () => {
        if (this.props.initialSelection) {
            this.setSelection(this.props.initialSelection);
        } else {
            this.maximizeSelection();
        }
    };

    @action setSelection(selection: SelectionData) {
        selection = this.normalize(selection);
        if (RectangleSelection.selectionsAreEqual(selection, this.selection)) {
            return;
        }

        this.selection = selection;
        if (this.props.onChange) {
            this.props.onChange(selection);
        }
    }

    normalize(selection: SelectionData): SelectionData {
        return this.normalizers.reduce((data, normalizer) => normalizer.normalize(data), selection);
    }

    applySelectionChange = (change: RectangleChange) => {
        this.setSelection({
            left: this.selection.left + change.left,
            top: this.selection.top + change.top,
            height: this.selection.height + change.height,
            width: this.selection.width + change.width,
        });
    };

    maximizeSelection = () => {
        this.setSelection(this.centerSelection(this.normalize({
            width: this.props.containerWidth,
            height: this.props.containerHeight,
            left: 0,
            top: 0,
        })));
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

    handleRectangleDoubleClick = this.maximizeSelection;
    handleRectangleChange = this.applySelectionChange;

    render() {
        let backdropSize = 0;

        if (this.props.containerHeight && this.props.containerWidth) {
            backdropSize = Math.max(this.props.containerHeight, this.props.containerWidth);
        }

        return (
            <div className={rectangleSelectionStyles.selection}>
                {this.props.children}
                <ModifiableRectangle
                    backdropSize={backdropSize}
                    height={this.selection.height}
                    left={this.selection.left}
                    onChange={this.handleRectangleChange}
                    onDoubleClick={this.handleRectangleDoubleClick}
                    top={this.selection.top}
                    width={this.selection.width}
                />
            </div>
        );
    }
}

export default withContainerSize(RectangleSelection, rectangleSelectionStyles.container);
