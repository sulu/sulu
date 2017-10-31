// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import {observer} from 'mobx-react';
import Button from './Button';
import Dropdown from './Dropdown';
import Select from './Select';
import toolbarStyles from './toolbar.scss';

type Children = Element<typeof Button> | Element<typeof Dropdown> | Element<typeof Select>
type Props = {
    children: ChildrenArray<Children>,
};

@observer
export default class Items extends React.PureComponent<Props> {
    render() {
        const {
            children,
        } = this.props;

        return (
            <ul className={toolbarStyles.items}>
                {children && React.Children.map(children, (item, index) => {
                    return <li key={index}>{item}</li>;
                })}
            </ul>
        );
    }
}
