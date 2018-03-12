// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Button from './Button';
import Controls from './Controls';
import Dropdown from './Dropdown';
import Items from './Items';
import Icons from './Icons';
import Select from './Select';
import type {Skin} from './types';
import toolbarStyles from './toolbar.scss';

type Props = {
    children: ChildrenArray<Element<typeof Controls>>,
    skin?: Skin,
};

export default class Toolbar extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'light',
    };

    static Button = Button;

    static Controls = Controls;

    static Dropdown = Dropdown;

    static Items = Items;

    static Icons = Icons;

    static Select = Select;

    static createChildren(children: ChildrenArray<Element<typeof Controls>>, skin?: Skin) {
        return React.Children.map(children, (child) => {
            return React.cloneElement(
                child,
                {
                    ...child.props,
                    skin: skin,
                }
            );
        });
    }

    render() {
        const {
            skin,
            children,
        } = this.props;

        const toolbarClass = classNames(
            toolbarStyles.toolbar,
            toolbarStyles[skin]
        );

        return (
            <nav className={toolbarClass}>
                {Toolbar.createChildren(children, skin)}
            </nav>
        );
    }
}
