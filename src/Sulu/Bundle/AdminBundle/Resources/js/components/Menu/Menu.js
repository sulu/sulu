// @flow
import React from 'react';
import type {ChildrenArray, ElementRef} from 'react';
import Divider from './Divider';
import menuStyles from './menu.scss';

type Props = {
    children?: ChildrenArray<ElementRef<'li'>>,
    menuRef?: (ref: ElementRef<'ul'>) => void,
    style?: Object,
};

export default class Menu extends React.PureComponent<Props> {
    static Divider = Divider;

    setRef = (ref: ElementRef<'ul'>) => {
        if (this.props.menuRef) {
            this.props.menuRef(ref);
        }
    };

    render() {
        const {
            style,
            children,
        } = this.props;

        return (
            <ul
                ref={this.setRef}
                style={style}
                className={menuStyles.menu}
            >
                {children}
            </ul>
        );
    }
}
