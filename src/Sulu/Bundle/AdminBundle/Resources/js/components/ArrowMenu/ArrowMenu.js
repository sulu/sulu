// @flow
import React, {Fragment} from 'react';
import type {ChildrenArray, Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import Popover from '../Popover';
import SingleItemSection from './SingleItemSection';
import Section from './Section';
import Item from './Item';
import Action from './Action';
import arrowMenuStyles from './arrowMenu.scss';

type Props = {
    children: ChildrenArray<Element<*>>,
    anchorElement: Element<*>,
    open: boolean,
    onClose?: () => void,
};

const VERTICAL_OFFSET = 20;

@observer
export default class ArrowMenu extends React.Component<Props> {
    static Section = Section;
    static SingleItemSection = SingleItemSection;
    static Item = Item;
    static Action = Action;

    @observable displayValueRef: ?ElementRef<*>;

    @action setDisplayValueRef = (ref: ?ElementRef<*>) => {
        this.displayValueRef = ref;
    };

    cloneAnchorElement = (anchorElement: Element<*>) => {
        return React.cloneElement(
            anchorElement,
            {
                ref: this.setDisplayValueRef,
            }
        );
    };

    render() {
        const {
            anchorElement,
            open,
            onClose,
        } = this.props;

        const clonedAnchorElement = this.cloneAnchorElement(anchorElement);

        return (
            <Fragment>
                {clonedAnchorElement}
                <Popover
                    anchorElement={this.displayValueRef}
                    onClose={onClose}
                    open={open}
                    verticalOffset={VERTICAL_OFFSET}
                >
                    {
                        (setPopoverElementRef, popoverStyle, verticalPosition, horizontalPosition) => {
                            const arrowVerticalPosition = verticalPosition === 'top' ? 'bottom' : 'top';

                            return this.renderMenu(
                                setPopoverElementRef,
                                popoverStyle,
                                arrowVerticalPosition,
                                horizontalPosition
                            );
                        }
                    }
                </Popover>
            </Fragment>
        );
    }

    renderMenu(
        setPopoverElementRef: (ref: ElementRef<*>) => void,
        popoverStyle: Object,
        arrowVerticalPosition: string = 'top',
        arrowHorizontalPosition: string = 'left'
    ) {
        const {
            children,
        } = this.props;

        const arrowClass = classNames(
            arrowMenuStyles.arrow,
            {
                [arrowMenuStyles.top]: arrowVerticalPosition === 'top',
                [arrowMenuStyles.bottom]: arrowVerticalPosition === 'bottom',
                [arrowMenuStyles.left]: arrowHorizontalPosition === 'left',
                [arrowMenuStyles.right]: arrowHorizontalPosition === 'right',
            }
        );

        return (
            <div className={arrowMenuStyles.arrowMenuContainer} ref={setPopoverElementRef} style={popoverStyle}>
                <div className={arrowClass} />
                <div className={arrowMenuStyles.arrowMenu}>
                    {children}
                </div>
            </div>
        );
    }
}
