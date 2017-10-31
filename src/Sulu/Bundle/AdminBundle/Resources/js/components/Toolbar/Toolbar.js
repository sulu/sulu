// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import {observer} from 'mobx-react';
import Button from './Button';
import Controls from './Controls';
import Dropdown from './Dropdown';
import Items from './Items';
import Select from './Select';
import toolbarStyles from './toolbar.scss';

type Children = Element<typeof Controls> | Element<typeof Button> | Element<typeof Dropdown> | Element<typeof Select>;
type Props = {
    children: ChildrenArray<Children>,
};

@observer
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
            <header className={toolbarStyles.toolbar}>
                <nav className={toolbarStyles.controlsContainer}>
                    {children}
                </nav>
            </header>
        );
    }
}
