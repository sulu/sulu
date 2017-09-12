// @flow
import React from 'react';
import Portal from 'react-portal';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import type {Node, ElementRef} from 'react';
import Backdrop from '../Backdrop';
import type {PopoverDimensions} from './types';
import PopoverPositioner from './PopoverPositioner';
import popoverStyles from './popover.scss';

type Props = {
    open: boolean,
    children?: (setPopoverElementRef: (ref: ElementRef<*>) => void, style: Object) => Node,
    onClose?: () => void,
    /** The element which will be used to position the popover */
    anchorElement: ElementRef<*>,
    centerChildElement?: ElementRef<*>,
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

    @observable popoverRef: ElementRef<'div'>;

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
        if (this.popoverRef) {
            this.popoverRef.scrollTop = this.scrollTop || 0;
        }
    }

    close = () => {
        if (this.props.onClose) {
            this.props.onClose();
        }
    };

    @computed get dimensions(): PopoverDimensions {
        const {
            anchorElement,
            verticalOffset,
            horizontalOffset,
            centerChildElement,
        } = this.props;
        const {
            top = 0,
            left = 0,
            width = 0,
            height = 0,
        } = anchorElement.getBoundingClientRect();
        const centerChildOffsetTop = (centerChildElement) ? centerChildElement.offsetTop : 0;
        const alignOnVerticalAnchorEdges = (!centerChildElement) ? true : false;

        return PopoverPositioner.getCroppedDimensions(
            this.scrollWidth,
            this.scrollHeight,
            top,
            left,
            width,
            height,
            horizontalOffset,
            verticalOffset,
            centerChildOffsetTop,
            alignOnVerticalAnchorEdges,
        );
    }

    updateDimensions() {
        const {
            scrollWidth,
            scrollHeight,
        } = this.popoverRef;

        this.setScrollDimensions(
            scrollWidth,
            scrollHeight,
        );
    }

    @action setScrollDimensions(scrollWidth: number, scrollHeight: number) {
        this.scrollWidth = scrollWidth;
        this.scrollHeight = scrollHeight;
    }

    handleBackdropClick = this.close;

    @action setPopoverRef = (popoverRef: ElementRef<*>) => {
        if (popoverRef) {
            this.popoverRef = popoverRef;
            this.updateDimensions();
        }
    };

    render() {
        const {
            open,
            children,
            anchorElement,
        } = this.props;

        if (!open || !anchorElement) {
            return null;
        }

        const dimensions = this.dimensions;
        const styles = Object.assign({}, PopoverPositioner.dimensionsToStyle(dimensions), {
            position: 'fixed',
            pointerEvents: 'auto',
        });
        this.scrollTop = dimensions.scrollTop;

        return (
            <div>
                <Portal isOpened={open}>
                    <div className={popoverStyles.container}>
                        {children &&
                            children(this.setPopoverRef, styles)
                        }
                    </div>
                </Portal>
                <Backdrop visible={false} open={open} onClick={this.handleBackdropClick} />
            </div>
        );
    }
}
