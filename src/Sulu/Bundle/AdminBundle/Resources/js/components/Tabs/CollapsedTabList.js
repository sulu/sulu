// @flow
import React from 'react';
import classNames from 'classnames';
import CollapsedTab from './CollapsedTab';
import collapsedTabListStyles from './collapsedTabList.scss';
import type {ChildrenArray, Element} from 'react';
import type {Type} from './types';

type Props = {
    children: ChildrenArray<Element<typeof CollapsedTab> | false>,
    type: Type,
};

export default class CollapsedTabList extends React.PureComponent<Props> {
    render() {
        const {
            children,
            type,
        } = this.props;

        const collapsedTabListClass = classNames(
            collapsedTabListStyles.collapsedTabList,
            collapsedTabListStyles[type]
        );

        return (
            <ul className={collapsedTabListClass}>
                {children}
            </ul>
        );
    }
}
