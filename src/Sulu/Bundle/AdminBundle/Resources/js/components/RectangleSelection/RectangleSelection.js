// @flow
import type {DataNormalizer, RectangleChange, SelectionData} from './types';
import {action, autorun, observable} from 'mobx';
import type {Children} from 'react';
import ModifiableRectangle from './ModifiableRectangle';
import PositionNormalizer from './dataNormalizers/PositionNormalizer';
import RatioNormalizer from './dataNormalizers/RatioNormalizer';
import React from 'react';
import RoundingNormalizer from './dataNormalizers/RoundingNormalizer';
import SizeNormalizer from './dataNormalizers/SizeNormalizer';
import {observer} from 'mobx-react';
import selectionStyles from './rectangleSelection.scss';

@observer
export default class RectangleSelection extends React.PureComponent {
    props: {
        /** Determines the position at which the selection box is rendered at the beginning */
        initialSelection?: SelectionData,
        minWidth?: number,
        minHeight?: number,
        /** Determines whether or not the data gets rounded */
        round: boolean,
        onChange?: (s: SelectionData) => void,
        children?: Children,
    };

    static defaultProps = {
        round: true,
    };

    /** Normalizers process the data returned from the rectangle before it's set as the selection data*/
    normalizers: Array<DataNormalizer> = [];
    container: HTMLElement;
    @observable containerWidth: number;
    @observable containerHeight: number;
    @observable selection: SelectionData = {top: 0, left: 0, width: 0, height: 0};

    static selectionsAreEqual(selection1: SelectionData, selection2: SelectionData) {
        return selection1.width === selection2.width
            && selection1.height === selection2.height
            && selection1.top === selection2.top
            && selection1.left === selection2.left;
    }

    componentDidMount() {
        // Although the children are loaded at this point, the browser could still be in
        // the process of rendering them (rendering is asynchronous). Wrapping the action
        // in requestAnimationFrame takes care of this matter.
        window.requestAnimationFrame(this.setInitialSelection);
        window.addEventListener('resize', this.handleWindowResize);
    }

    componentWillUnmount() {
        this.normalizersDisposer();
        window.removeEventListener('resize', this.handleWindowResize);
    }

    setInitialSelection = () => {
        if (this.props.initialSelection) {
            this.setSelection(this.props.initialSelection);
        } else {
            this.maximizeSelection();
        }
    };

    @action setSelection(selection: SelectionData) {
        selection = this.normalize(selection);
        if (RectangleSelection.selectionsAreEqual(selection, this.selection)) return;

        this.selection = selection;
        if (this.props.onChange) {
            this.props.onChange(selection);
        }
    }

    normalizersDisposer = autorun(() => {
        if (!this.containerWidth || !this.containerHeight) return;

        this.normalizers = [];
        this.normalizers.push(new SizeNormalizer(
            this.containerWidth,
            this.containerHeight,
            this.props.minWidth,
            this.props.minHeight
        ));
        this.normalizers.push(new PositionNormalizer(this.containerWidth, this.containerHeight));
        if (this.props.minWidth && this.props.minHeight) {
            this.normalizers.push(new RatioNormalizer(this.props.minWidth, this.props.minHeight));
        }
        if (this.props.round) {
            this.normalizers.push(new RoundingNormalizer());
        }
        // make sure the current selection is normalized
        this.setSelection(this.selection);
    });

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
            width: this.containerWidth,
            height: this.containerHeight,
            left: 0,
            top: 0,
        })));
    };

    centerSelection(selection: SelectionData): SelectionData {
        if (selection.width < this.containerWidth) {
            selection.left = (this.containerWidth / 2) - (selection.width / 2);
        }
        if (selection.height < this.containerHeight) {
            selection.top = (this.containerHeight / 2) - (selection.height / 2);
        }
        
        return selection;
    }

    readContainerDimensions = (container: HTMLElement) => {
        window.requestAnimationFrame(() => {
            this.container = container;
            this.containerWidth = container.clientWidth;
            this.containerHeight = container.clientHeight;
        });
    };

    handleWindowResize = () => this.readContainerDimensions(this.container);
    handleRectangleDoubleClick = this.maximizeSelection;
    handleRectangleChange = this.applySelectionChange;

    render() {
        let backdropSize = 0;
        if (this.containerHeight && this.containerWidth) {
            backdropSize = Math.max(this.containerHeight, this.containerWidth);
        }

        return (
            <div ref={this.readContainerDimensions} className={selectionStyles.selection}>
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
