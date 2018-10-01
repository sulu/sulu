// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Button from './Button';
import Controls from './Controls';
import Dropdown from './Dropdown';
import Snackbar from './Snackbar';
import Items from './Items';
import Icons from './Icons';
import Toggler from './Toggler';
import Select from './Select';
import type {Skin} from './types';
import toolbarStyles from './toolbar.scss';

type Props = {
    children: ChildrenArray<false | Element<typeof Controls | typeof Snackbar>>,
    hasWhitespace: boolean,
    skin?: Skin,
};

export default class Toolbar extends React.PureComponent<Props> {
    static defaultProps = {
        hasWhitespace: false,
        skin: 'light',
    };

    static Button = Button;
    static Controls = Controls;
    static Dropdown = Dropdown;
    static Items = Items;
    static Icons = Icons;
    static Select = Select;
    static Toggler = Toggler;
    static Snackbar = Snackbar;

    static createChildren(children: ChildrenArray<*>, skin?: Skin) {
        return React.Children.map(children, (child) => {
            if (!child) {
                return null;
            }

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
            children,
            hasWhitespace,
            skin,
        } = this.props;

        const toolbarClass = classNames(
            toolbarStyles.toolbar,
            toolbarStyles[skin],
            {
                [toolbarStyles.whitespace]: hasWhitespace,
            }
        );

        return (
            <nav className={toolbarClass}>
                {Toolbar.createChildren(children, skin)}
            </nav>
        );
    }
}
