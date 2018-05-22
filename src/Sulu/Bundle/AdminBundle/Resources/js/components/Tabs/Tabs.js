// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Tab from './Tab';
import tabsStyles from './tabs.scss';

type Props = {
    children: ChildrenArray<Element<typeof Tab>>,
    onSelect: (tabIndex: number) => void,
    selectedIndex: ?number,
};

export default class Tabs extends React.PureComponent<Props> {
    static Tab = Tab;

    isSelected(tabIndex: number) {
        return tabIndex === this.props.selectedIndex;
    }

    createTabItems(tabs: ChildrenArray<Element<typeof Tab>>) {
        return React.Children.map(tabs, (tab, index) => {
            return React.cloneElement(
                tab,
                {
                    ...tab.props,
                    index: index,
                    selected: this.isSelected(index),
                    onClick: this.handleSelectionChange,
                }
            );
        });
    }

    handleSelectionChange = (selectedTabIndex: ?number) => {
        if (typeof(selectedTabIndex) !== 'undefined' && selectedTabIndex !== null) {
            this.props.onSelect(selectedTabIndex);
        }
    };

    render() {
        const {
            children,
        } = this.props;
        const tabsItems = this.createTabItems(children);

        return (
            <div>
                <ul className={tabsStyles.tabsMenu}>
                    {tabsItems}
                </ul>
            </div>
        );
    }
}
