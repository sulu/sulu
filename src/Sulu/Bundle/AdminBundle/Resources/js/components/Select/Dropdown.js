// @flow
import {action, computed, observable} from 'mobx';
import Backdrop from '../Backdrop';
import Option from './Option';
import Portal from 'react-portal';
import React from 'react';
import dropdownStyles from './dropdown.scss';
import {observer} from 'mobx-react';

type Dimensions = {
    top: number,
    left: number,
    height: number,
    scrollTop: number,
}

@observer
export default class Dropdown extends React.PureComponent {
    props: {
        isOpen: boolean,
        children?: React.Element<*>,
        onRequestClose?: () => void,
        labelTop: number,
        labelLeft: number,
        centeredChildIndex: number,
    };

    @observable naturalHeight: number;
    @observable centeredChildRelativeTop: number;
    list: HTMLElement;
    scrollTop: number;

    static defaultProps = {
        isOpen: false,
        centeredChildIndex: 0,
    };

    componentDidUpdate() {
        if (this.list) {
            window.requestAnimationFrame(() => {
                this.list.scrollTop = this.scrollTop;
            });
        }
    }

    @computed get dimensions(): ?Dimensions {
        if (!this.naturalHeight || !this.centeredChildRelativeTop) {
            return null;
        }

        return this.cropDimensionsTop({
            top: (this.props.labelTop - this.centeredChildRelativeTop),
            left: this.props.labelLeft,
            height: this.naturalHeight,
            scrollTop: 0,
        });
    }

    cropDimensionsTop(dimensions: Dimensions): Dimensions {
        if (dimensions.top < 0) {
            return {
                top: 0,
                height: dimensions.height + dimensions.top,
                left: dimensions.left,
                scrollTop: dimensions.top * (-1),
            };
        }
        return dimensions;
    }

    cropDimensionsBottom(dimensions: Dimensions): Dimensions {
        

        return {
            top: 0,
            height: dimensions.height + dimensions.top,
            left: dimensions.left,
            scrollTop: dimensions.top * (-1),
        };
    }

    dimensionsToStyle(dimensions: Dimensions): {top: string, height: string, left: string} {
        return {
            top: dimensions.top + 'px',
            height: dimensions.height + 'px',
            left: dimensions.left + 'px',
        };
    }

    readCenteredChildRelativeTop = (option: Option) => {
        if (option) {
            window.requestAnimationFrame(action(() => {
                this.centeredChildRelativeTop = option.getOffsetTop();
            }));
        }
    };

    readNaturalHeight = (list: HTMLElement) => {
        this.list = list;
        if (list && !this.naturalHeight) {
            window.requestAnimationFrame(action(() => {
                this.naturalHeight = list.clientHeight;
            }));
        }
    };

    render() {
        const children = React.Children.toArray(this.props.children);
        const centeredChildIsDisabled = children[this.props.centeredChildIndex].props.disabled;
        let focus = true;
        let style;
        const dimensions = this.dimensions;
        if (dimensions) {
            style = this.dimensionsToStyle(dimensions);
            this.scrollTop = dimensions.scrollTop;
        }

        return (
            <div>
                <Portal isOpened={this.props.isOpen}>
                    <ul ref={this.readNaturalHeight} style={style} className={dropdownStyles.dropdown}>
                        {React.Children.map(this.props.children, (child, index) => {
                            const props = {};

                            if (index === this.props.centeredChildIndex) {
                                props.ref = this.readCenteredChildRelativeTop;
                                props.focus = !centeredChildIsDisabled;
                            }

                            if (centeredChildIsDisabled && !child.props.disabled) {
                                props.focus = focus;
                                focus = false;
                            }
                            return React.cloneElement(child, props);
                        })}
                    </ul>
                </Portal>
                <Backdrop isVisible={false} isOpen={this.props.isOpen} onClick={this.props.onRequestClose} />
            </div>
        );
    }
}
