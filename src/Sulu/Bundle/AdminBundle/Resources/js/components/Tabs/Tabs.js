// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Tab from './Tab';
import tabsStyles from './tabs.scss';

type Props = {
    children: ChildrenArray<Element<typeof Tab>>,
    value: string | number,
    onChange: (value: string | number) => void,
};

export default class Tabs extends React.PureComponent<Props> {
    static Tab = Tab;

    isSelected(tabValue: string | number) {
        return tabValue === this.props.value;
    }

    createTabMenuItems(tabs: ChildrenArray<Element<typeof Tab>>) {
        return React.Children.map(tabs, (tab) => {
            return React.cloneElement(
                tab,
                {
                    ...tab.props,
                    selected: this.isSelected(tab.props.value),
                    onClick: this.handleSelectionChange,
                }
            );
        });
    }

    getSelectedTabContent(tabs: ChildrenArray<Element<typeof Tab>>) {
        const tabsArray = React.Children.toArray(tabs);
        const selectedTab = tabsArray.find((tab) => this.isSelected(tab.props.value));

        if (!selectedTab || !selectedTab.props) {
            return null;
        }

        return selectedTab.props.children;
    }

    handleSelectionChange = (selectedTabValue: string | number) => {
        this.props.onChange(selectedTabValue);
    };

    render() {
        const {
            children,
        } = this.props;
        const tabsMenuItems = this.createTabMenuItems(children);
        const content = this.getSelectedTabContent(children);

        return (
            <div>
                <ul className={tabsStyles.tabsMenu}>
                    {tabsMenuItems}
                </ul>
                <div className={tabsStyles.content}>
                    {content}
                </div>
            </div>
        );
    }
}
