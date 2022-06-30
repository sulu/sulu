// @flow
import React, {Fragment} from 'react';
import {Portal} from 'react-portal';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import Mousetrap from 'mousetrap';
import {afterElementsRendered} from '../../utils/DOM';
import Backdrop from '../Backdrop';
import PopoverPositioner from './PopoverPositioner';
import popoverStyles from './popover.scss';
import type {PopoverDimensions} from './types';
import type {ElementRef, Node} from 'react';

type Props = {
    /** This element will be used to position the popover */
    anchorElement: ElementRef<*>,
    backdrop: boolean,
    centerChildElement?: ElementRef<*>,
    children?: (
        setPopoverElementRef: (ref: ElementRef<*>) => void,
        style: Object,
        verticalPosition: string,
        horizontalPosition: string,
    ) => Node,
    horizontalCenter?: boolean,
    horizontalOffset: number,
    onClose?: () => void,
    open: boolean,
    popoverChildRef?: (ref: ?ElementRef<*>) => void,
    verticalOffset: number,
};

const CLOSE_KEY = 'esc';

@observer
class Popover extends React.Component<Props> {
    static defaultProps = {
        backdrop: true,
        horizontalCenter: false,
        horizontalOffset: 0,
        open: false,
        verticalOffset: 0,
    };

    @observable popoverChildRef: ElementRef<*>;
    @observable popoverWidth: number;
    @observable popoverHeight: number;

    mutationObserver: MutationObserver;

    constructor(props: Props) {
        super(props);

        window.addEventListener('blur', this.close);
        window.addEventListener('resize', this.close);
        this.mutationObserver = new MutationObserver(() => {
            // The size of the popover has to be reset before updating the dimensions, because otherwise the old style
            // including width and height will still apply, and therefore the dimensions would not change
            this.setPopoverSize(0, 0);
            this.updateDimensions();
        });

        if (this.props.open) {
            Mousetrap.bind(CLOSE_KEY, this.close);
        }
    }

    componentWillUnmount() {
        window.removeEventListener('blur', this.close);
        window.removeEventListener('resize', this.close);
        this.mutationObserver.disconnect();

        if (this.props.open) {
            Mousetrap.unbind(CLOSE_KEY);
        }
    }

    componentDidUpdate(prevProps: Props) {
        if (this.popoverChildRef) {
            this.updateDimensions();

            afterElementsRendered(() => {
                this.popoverChildRef.scrollTop = this.dimensions.scrollTop;
            });
        }

        if (prevProps.open !== this.props.open) {
            if (this.props.open) {
                Mousetrap.bind(CLOSE_KEY, this.close);
            } else {
                Mousetrap.unbind(CLOSE_KEY);
            }
        }
    }

    close = () => {
        const {open, onClose} = this.props;

        if (open && onClose) {
            onClose();
        }
    };

    @computed get dimensions(): PopoverDimensions {
        const {
            anchorElement,
            verticalOffset,
            horizontalCenter,
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
        const alignOnVerticalAnchorEdges = !centerChildElement;

        const horizontalCenterValue = horizontalCenter ? (width - this.popoverWidth) / 2 : 0;

        return PopoverPositioner.getCroppedDimensions(
            this.popoverWidth,
            this.popoverHeight,
            top,
            left,
            width,
            height,
            horizontalCenterValue + horizontalOffset,
            verticalOffset,
            centerChildOffsetTop,
            alignOnVerticalAnchorEdges
        );
    }

    updateDimensions = () => {
        if (!this.popoverChildRef) {
            return;
        }

        const {
            clientHeight,
            clientWidth,
            offsetHeight,
            offsetWidth,
            scrollHeight,
            scrollWidth,
        } = this.popoverChildRef;

        // calculating real size by considering borders, margins and paddings
        this.setPopoverSize(
            scrollWidth + offsetWidth - clientWidth,
            scrollHeight + offsetHeight - clientHeight
        );
    };

    @action setPopoverSize(width: number, height: number) {
        this.popoverWidth = width;
        this.popoverHeight = height;
    }

    handleBackdropClick = this.close;

    @action setPopoverChildRef = (ref: ElementRef<*>) => {
        if (ref) {
            this.popoverChildRef = ref;
            this.mutationObserver.disconnect();
            this.mutationObserver.observe(this.popoverChildRef, {childList: true, subtree: true});
        }

        const {popoverChildRef} = this.props;
        if (popoverChildRef) {
            popoverChildRef(ref);
        }
    };

    render() {
        const {
            open,
            children,
            anchorElement,
            backdrop,
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

        const verticalPosition = (dimensions.top > anchorElement.getBoundingClientRect().top) ? 'bottom' : 'top';
        const horizontalPosition = (dimensions.left === anchorElement.getBoundingClientRect().left) ? 'left' : 'right';

        return (
            <Fragment>
                <Portal>
                    {backdrop && <Backdrop onClick={this.handleBackdropClick} visible={false} />}
                    <div className={popoverStyles.container}>
                        {children &&
                            children(this.setPopoverChildRef, styles, verticalPosition, horizontalPosition)
                        }
                    </div>
                </Portal>
            </Fragment>
        );
    }
}

export default Popover;
