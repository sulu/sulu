// @flow
import React from 'react';
import Portal from 'react-portal';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import type {ChildrenArray, ElementRef} from 'react';
import Backdrop from '../Backdrop';
import type {PopoverDimensions} from './types';
import PopoverPositioner from './PopoverPositioner';
import popoverStyles from './popover.scss';

type Props = {
    open: boolean,
    children: ChildrenArray<*>,
    onClose?: () => void,
    /** The element which will be used to position the popover */
    anchorEl: ElementRef<*>,
    centerChildNode: ElementRef<*>,
    horizontalOffset: number,
    verticalOffset: number,
};

@observer
export default class Popover extends React.PureComponent<Props> {
    static defaultProps = {
        open: false,
        horizontalOffset: 0,
        verticalOffset: 0,
    };

    popoverEl: ElementRef<'div'>;

    @observable scrollHeight: number;

    @observable scrollWidth: number;

    scrollTop: number;

    componentDidMount() {
        window.addEventListener('blur', this.close);
        window.addEventListener('resize', this.close);
    }

    componentWillUnmount() {
        window.removeEventListener('blur', this.close);
        window.removeEventListener('resize', this.close);
    }

    componentDidUpdate() {
        if (this.popoverEl) {
            this.popoverEl.scrollTop = this.scrollTop || 0;
        }
    }

    close = () => {
        if (this.props.onClose) {
            this.props.onClose();
        }
    };

    @computed get dimensions(): PopoverDimensions {
        const {
            anchorEl,
            verticalOffset,
            horizontalOffset,
            centerChildNode,
        } = this.props;
        const {
            top = 0,
            left = 0,
            width = 0,
            height = 0,
        } = anchorEl.getBoundingClientRect();
        const newVerticalOffset = (centerChildNode) ? -centerChildNode.offsetTop + verticalOffset : verticalOffset;
        const alignOnVerticalAnchorEdges = (centerChildNode) ? false : true;

        return PopoverPositioner.getCroppedDimensions(
            this.scrollWidth,
            this.scrollHeight,
            top,
            left,
            width,
            height,
            horizontalOffset,
            newVerticalOffset,
            alignOnVerticalAnchorEdges,
        );
    }

    @action setScrollDimensions(scrollWidth: number, scrollHeight: number) {
        this.scrollWidth = scrollWidth;
        this.scrollHeight = scrollHeight;
    }

    handleBackropClick = this.close;

    setPopoverNode = (popoverEl: ElementRef<'div'>) => {
        if (!popoverEl) {
            return;
        }

        this.popoverEl = popoverEl;

        const {
            scrollWidth,
            scrollHeight,
        } = popoverEl;
        const borderWidth = parseInt(window.getComputedStyle(popoverEl).borderWidth, 10) * 2;

        this.setScrollDimensions(
            scrollWidth + borderWidth,
            scrollHeight + borderWidth,
        );
    };

    render() {
        if (!this.props.open) {
            return null;
        }

        const dimensions = this.dimensions;
        const style = PopoverPositioner.dimensionsToStyle(dimensions);
        this.scrollTop = dimensions.scrollTop;

        return (
            <div>
                <Portal isOpened={this.props.open}>
                    <div className={popoverStyles.container}>
                        <div
                            style={style}
                            ref={this.setPopoverNode}
                            className={popoverStyles.popover}>
                            {this.props.children}
                        </div>
                    </div>
                </Portal>
                <Backdrop visible={false} open={this.props.open} onClick={this.handleBackropClick} />
            </div>
        );
    }
}
