// @flow
import Portal from 'react-portal';
import {observer} from 'mobx-react';
import React from 'react';
import {action, computed, observable} from 'mobx';
import Backdrop from '../Backdrop';
import Option from './Option';
import overlayListStyles from './overlayList.scss';
import type {OverlayListDimensions} from './types';
import OverlayListPositioner from './OverlayListPositioner';

@observer
export default class OverlayList extends React.PureComponent {
    props: {
        isOpen: boolean,
        children?: React.Element<*>,
        onRequestClose?: () => void,
        /** The top coordinate relative to which the list will be positioned **/
        anchorTop: number,
        /** The left coordinate relative to which the list will be positioned **/
        anchorLeft: number,
        /** The width of the element relative to which the list will be positioned **/
        anchorWidth: number,
        /** The width of the element relative to which the list will be positioned **/
        anchorHeight: number,
        /** The index of the child element which will be centered relative to the anchor **/
        centeredChildIndex: number,
    };

    static defaultProps = {
        isOpen: false,
        centeredChildIndex: 0,
        anchorTop: 0,
        anchorLeft: 0,
        anchorWidth: 0,
        anchorHeight: 0,
    };

    @observable offsetHeight: number;
    @observable offsetWidth: number;
    @observable centeredChildRelativeTop: number;
    list: HTMLElement;
    scrollTop: number;

    componentDidMount() {
        window.addEventListener('blur', this.requestClose);
        window.addEventListener('resize', this.requestClose);
    }

    componentWillUnmount() {
        window.removeEventListener('blur', this.requestClose);
        window.removeEventListener('resize', this.requestClose);
    }

    componentDidUpdate() {
        window.requestAnimationFrame(() => {
            if (this.list) {
                this.list.scrollTop = this.scrollTop;
            }
        });
    }

    requestClose = () => {
        if (this.props.isOpen && this.props.onRequestClose) {
            this.props.onRequestClose();
        }
    };

    @computed get dimensions(): ?OverlayListDimensions {
        if (!this.offsetHeight || !this.offsetHeight || !this.centeredChildRelativeTop) {
            return null;
        }
        const positioner = new OverlayListPositioner(
            this.offsetHeight,
            this.offsetWidth,
            this.centeredChildRelativeTop,
            this.props.anchorTop,
            this.props.anchorLeft,
            this.props.anchorWidth,
            this.props.anchorHeight,
        );
        return positioner.getCroppedDimensions();
    }

    readCenteredChildRelativeTop = (option: Option) => {
        if (option) {
            window.requestAnimationFrame(action(() => {
                this.centeredChildRelativeTop = option.getOffsetTop();
            }));
        }
    };

    readOffsetDimensions = (list: HTMLElement) => {
        this.list = list;
        if (!list || this.offsetWidth || this.offsetHeight) {
            return;
        }
        window.requestAnimationFrame(action(() => {
            this.offsetWidth = list.offsetWidth;
            this.offsetHeight = list.offsetHeight;
        }));
    };

    render() {
        let style = {visibility: 'hidden'};
        const dimensions = this.dimensions;
        if (dimensions) {
            style = OverlayListPositioner.dimensionsToStyle(dimensions);
            this.scrollTop = dimensions.scrollTop;
        }

        return (
            <div>
                <Portal isOpened={this.props.isOpen}>
                    <div className={overlayListStyles.container}>
                        <ul
                            ref={this.readOffsetDimensions}
                            style={style}
                            className={overlayListStyles.list}>
                            {this.renderChildrenWithFocusSet()}
                        </ul>
                    </div>
                </Portal>
                <Backdrop isVisible={false} isOpen={this.props.isOpen} onClick={this.requestClose} />
            </div>
        );
    }

    renderChildrenWithFocusSet() {
        const children = React.Children.toArray(this.props.children);
        const centeredChildIsDisabled = children[this.props.centeredChildIndex].props.disabled;
        let focus = true;

        return React.Children.map(this.props.children, (child, index) => {
            const props = {};

            if (index === this.props.centeredChildIndex) {
                props.ref = this.readCenteredChildRelativeTop;
                props.focus = !centeredChildIsDisabled;
            }

            // if the child which gets centered is disabled, the first not disabled element receives the focus
            if (centeredChildIsDisabled && !child.props.disabled) {
                props.focus = focus;
                focus = false;
            }
            return React.cloneElement(child, props);
        });
    }
}
