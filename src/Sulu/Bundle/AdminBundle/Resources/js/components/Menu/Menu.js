// @flow
import React from 'react';
import Divider from './Divider';
import menuStyles from './menu.scss';
import type {ChildrenArray, Element, ElementRef} from 'react';

type Props = {
    children?: ChildrenArray<Element<*> | false>,
    menuRef?: (ref: ElementRef<'ul'>) => void,
    style?: Object,
};

export default class Menu extends React.PureComponent<Props> {
    static Divider = Divider;

    setRef = (ref: ?ElementRef<'ul'>) => {
        const {menuRef} = this.props;
        if (menuRef && ref) {
            menuRef(ref);
        }
    };

    render() {
        const {
            style,
            children,
        } = this.props;

        return (
            <ul
                className={menuStyles.menu}
                ref={this.setRef}
                style={style}
            >
                {children}
            </ul>
        );
    }
}