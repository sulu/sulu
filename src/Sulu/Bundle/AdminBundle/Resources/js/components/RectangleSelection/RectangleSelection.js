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
import selectionStyles from './rectangleSelection.scss';

@observer
export default class RectangleSelection extends React.PureComponent {
    props: {
        /** Determines the position at which the selection box is rendered at the beginning. */
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
    @observable selection: SelectionData = {top: 0, left: 0, width: 0, height: 0};

    componentDidMount() {
        this.initializeSelection();
    }

    @action initializeSelection() {
        // Although the children are loaded at this point, the browser could still be in
        // the process of rendering them (rendering is asynchronous). Wrapping the action
        // in requestAnimationFrame takes care of this matter.
        window.requestAnimationFrame(action(() => {
            this.initializeNormalizers();
            if (this.props.initialSelection) {
                this.setSelection(this.props.initialSelection);
            } else {
                this.maximizeSelection();
            }
        }));
    }

    setSelection(selection: SelectionData) {
        this.selection = selection;
        if (this.props.onChange) {
            this.props.onChange(selection);
        }
    }

    initializeNormalizers() {
        this.normalizers.push(new SizeNormalizer(
            this.container.clientWidth,
            this.container.clientHeight,
            this.props.minWidth,
            this.props.minHeight
        ));
        this.normalizers.push(new PositionNormalizer(this.container.clientWidth, this.container.clientHeight));
        if (this.props.minWidth && this.props.minHeight) {
            this.normalizers.push(new RatioNormalizer(this.props.minWidth, this.props.minHeight));
        }
        if (this.props.round) {
            this.normalizers.push(new RoundingNormalizer());
        }
    }

    normalize(selection: SelectionData): SelectionData {
        return this.normalizers.reduce((data, normalizer) => normalizer.normalize(data), selection);
    }

    @action applySelectionChange = (change: RectangleChange) => {
        this.setSelection(this.normalize({
            left: this.selection.left + change.left,
            top: this.selection.top + change.top,
            height: this.selection.height + change.height,
            width: this.selection.width + change.width,
        }));
    };

    @action maximizeSelection = () => {
        this.setSelection(this.centerSelection(this.normalize({
            width: this.container.clientWidth,
            height: this.container.clientHeight,
            left: 0,
            top: 0,
        })));
    };

    centerSelection(selection: SelectionData): SelectionData {
        if (selection.width < this.container.clientWidth) {
            selection.left = (this.container.clientWidth / 2) - (selection.width / 2);
        }
        if (selection.height < this.container.clientHeight) {
            selection.top = (this.container.clientHeight / 2) - (selection.height / 2);
        }
        
        return selection;
    }

    setContainer = (el: HTMLElement) => {
        this.container = el;
    };

    handleRectangleDoubleClick = this.maximizeSelection;
    handleRectangleChange = this.applySelectionChange;

    render() {
        let backdropSize = 0;
        if (this.container) {
            backdropSize = Math.max(this.container.clientHeight, this.container.clientWidth);
        }

        return (
            <div ref={this.setContainer} className={selectionStyles.selection}>
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
