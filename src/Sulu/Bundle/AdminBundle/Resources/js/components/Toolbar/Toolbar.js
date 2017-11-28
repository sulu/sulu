// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Button from './Button';
import Controls from './Controls';
import Dropdown from './Dropdown';
import Items from './Items';
import Select from './Select';
import toolbarStyles from './toolbar.scss';

type Props = {
    children: ChildrenArray<Element<typeof Controls>>,
};

export default class Toolbar extends React.PureComponent<Props> {
    static Button = Button;

    static Controls = Controls;

    static Dropdown = Dropdown;

    static Items = Items;

    static Select = Select;

    render() {
        const {
            children,
        } = this.props;

        return (
            <nav className={toolbarStyles.toolbar}>
                {children}
            </nav>
        );
    }
}
