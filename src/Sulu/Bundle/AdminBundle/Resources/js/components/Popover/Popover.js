// @flow
import React from 'react';
import Portal from 'react-portal';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import type {Node, ElementRef} from 'react';
import {afterElementsRendered} from '../../services/DOM';
import Backdrop from '../Backdrop';
import type {PopoverDimensions} from './types';
import PopoverPositioner from './PopoverPositioner';
import popoverStyles from './popover.scss';

type Props = {
    open: boolean,
    children?: (setPopoverElementRef: (ref: ElementRef<*>) => void, style: Object) => Node,
    onClose?: () => void,
    /** This element will be used to position the popover */
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

    @observable popoverChildRef: ElementRef<'div'>;

    @observable popoverWidth: number;

    @observable popoverHeight: number;

    componentDidMount() {
        window.addEventListener('blur', this.close);
        window.addEventListener('resize', this.close);
    }

    componentWillUnmount() {
        window.removeEventListener('blur', this.close);
        window.removeEventListener('resize', this.close);
    }

    componentDidUpdate(prevProps: Props) {
        if (this.popoverChildRef && !prevProps.open && this.props.open) {
            afterElementsRendered(() => {
                this.popoverChildRef.scrollTop = this.dimensions.scrollTop;
            });
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
            this.popoverWidth,
            this.popoverHeight,
            top,
            left,
            width,
            height,
            horizontalOffset,
            verticalOffset,
            centerChildOffsetTop,
            alignOnVerticalAnchorEdges
        );
    }

    updateDimensions() {
        const {
            offsetWidth,
            offsetHeight,
            scrollWidth,
            scrollHeight,
            clientWidth,
            clientHeight,
        } = this.popoverChildRef;

        // calculating real size by considering borders, margins and paddings
        const outerWidth = scrollWidth + offsetWidth - clientWidth;
        const outerHeight = scrollHeight + offsetHeight - clientHeight;

        this.setPopoverSize(
            outerWidth,
            outerHeight
        );
    }

    @action setPopoverSize(width: number, height: number) {
        this.popoverWidth = width;
        this.popoverHeight = height;
    }

    handleBackdropClick = this.close;

    @action setPopoverChildRef = (popoverChildRef: ElementRef<*>) => {
        if (popoverChildRef) {
            this.popoverChildRef = popoverChildRef;
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
        const styles = {
            ...PopoverPositioner.dimensionsToStyle(dimensions),
            position: 'fixed',
            pointerEvents: 'auto',
        };

        return (
            <div>
                <Backdrop visible={false} open={open} onClick={this.handleBackdropClick} />
                <Portal isOpened={open}>
                    <div className={popoverStyles.container}>
                        {children &&
                            children(this.setPopoverChildRef, styles)
                        }
                    </div>
                </Portal>
            </div>
        );
    }
}
