// @flow
import React from 'react';
import classNames from 'classnames';
import CollapsedTab from './CollapsedTab';
import collapsedTabListStyles from './collapsedTabList.scss';
import type {Skin} from './types';
import type {ChildrenArray, Element} from 'react';

type Props = {
    children: ChildrenArray<Element<typeof CollapsedTab> | false>,
    skin: Skin,
};

export default class CollapsedTabList extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'default',
    };

    render() {
        const {
            children,
            skin,
        } = this.props;

        const collapsedTabListClass = classNames(
            collapsedTabListStyles.collapsedTabList,
            collapsedTabListStyles[skin]
        );

        return (
            <ul className={collapsedTabListClass}>
                {children}
            </ul>
        );
    }
}
