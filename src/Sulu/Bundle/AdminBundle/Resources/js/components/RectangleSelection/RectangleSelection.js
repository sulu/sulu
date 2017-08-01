// @flow
import type {DataNormalizer, RectangleChange, SelectionData} from './types';
import {action, observable} from 'mobx';
import type {Children} from 'react';
import ModifiableRectangle from './ModifiableRectangle';
import PositionNormalizer from './dataNormalizers/PositionNormalizer';
import RatioNormalizer from './dataNormalizers/RatioNormalizer';
import React from 'react';
import RoundingNormalizer from './dataNormalizers/RoundingNormalizer';
import SizeNormalizer from './dataNormalizers/SizeNormalizer';
import {observer} from 'mobx-react';
import rectangleSelectionStyles from './rectangleSelection.scss';
import withContainerSize from '../withContainerSize';

@observer
export class RectangleSelection extends React.PureComponent {
    props: {
        /** Determines the position at which the selection box is rendered at the beginning */
        initialSelection?: SelectionData,
        minWidth?: number,
        minHeight?: number,
        /** Determines whether or not the data gets rounded */
        round: boolean,
        onChange?: (s: SelectionData) => void,
        children?: Children,
        containerHeight: number,
        containerWidth: number,
    };

    static defaultProps = {
        round: true,
    };

    @observable selection: SelectionData = {top: 0, left: 0, width: 0, height: 0};

    static selectionsAreEqual(selection1: SelectionData, selection2: SelectionData) {
        return selection1.width === selection2.width
            && selection1.height === selection2.height
            && selection1.top === selection2.top
            && selection1.left === selection2.left;
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

    get normalizers(): Array<DataNormalizer> {
        if (!this.props.containerWidth || !this.props.containerHeight) {
            return [];
        }

        const normalizers = [];
        normalizers.push(new SizeNormalizer(
            this.props.containerWidth,
            this.props.containerHeight,
            this.props.minWidth,
            this.props.minHeight
        ));
        normalizers.push(new PositionNormalizer(this.props.containerWidth, this.props.containerHeight));
        if (this.props.minWidth && this.props.minHeight) {
            normalizers.push(new RatioNormalizer(this.props.minWidth, this.props.minHeight));
        }
        if (this.props.round) {
            normalizers.push(new RoundingNormalizer());
        }

        return normalizers;
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
                    onChange={this.handleRectangleChange}
                    onDoubleClick={this.handleRectangleDoubleClick}
                    left={this.selection.left}
                    top={this.selection.top}
                    width={this.selection.width}
                    height={this.selection.height}
                    backdropSize={backdropSize} />
            </div>
        );
    }
}

export default withContainerSize(RectangleSelection, rectangleSelectionStyles.container);
