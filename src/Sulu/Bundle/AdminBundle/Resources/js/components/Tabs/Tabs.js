// @flow
import React from 'react';
import type {ChildrenArray, Element, ElementRef} from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import debounce from 'debounce';
import Popover from '../Popover';
import Icon from '../Icon';
import type {Skin} from './types';
import Tab from './Tab';
import CollapsedTabList from './CollapsedTabList';
import CollapsedTab from './CollapsedTab';
import tabsStyles from './tabs.scss';

type Props = {
    children: ChildrenArray<Element<typeof Tab> | false>,
    onSelect: (tabIndex: number) => void,
    selectedIndex: ?number,
    skin: Skin,
    small: boolean,
};

const DEBOUNCE_TIME = 200;

@observer
class Tabs extends React.Component<Props> {
    static defaultProps = {
        skin: 'default',
        small: false,
    };

    @observable tabsWidth: number = 0;
    @observable tabsContainerWrapperWidth: number = 0;
    @observable tabsContainerWidth: number = 0;

    @observable tabWidths: Map<number, number> = new Map();
    @observable tabRefs: Map<number, ?ElementRef<'li'>> = new Map();
    @observable dropdownOpen = false;
    @observable lastShownIndex: ?number;

    static Tab = Tab;

    resizeObserver: ?ResizeObserver;

    tabsRef: ?ElementRef<'div'>;
    tabsContainerWrapperRef: ?ElementRef<'div'>;
    tabsContainerRef: ?ElementRef<'ul'>;

    componentDidMount() {
        this.setDimensions();

        this.resizeObserver = new ResizeObserver(
            debounce(this.setDimensions, DEBOUNCE_TIME)
        );

        if (this.tabsContainerWrapperRef) {
            this.resizeObserver.observe(this.tabsContainerWrapperRef);
        }

        if (this.tabsContainerRef) {
            this.resizeObserver.observe(this.tabsContainerRef);
        }
    }

    componentWillUnmount() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
    }

    setTabsRef = (ref: ?ElementRef<'div'>) => {
        this.tabsRef = ref;
    };

    setTabsContainerWrapperRef = (ref: ?ElementRef<'div'>) => {
        this.tabsContainerWrapperRef = ref;
    };

    setTabsContainerRef = (ref: ?ElementRef<'ul'>) => {
        this.tabsContainerRef = ref;
    };

    @action setTabsWidth = () => {
        if (!this.tabsRef) {
            return;
        }

        const width = this.tabsRef.offsetWidth;
        const style = window.getComputedStyle(this.tabsRef);
        if (this.tabsWidth !== width) {
            this.tabsWidth = width - parseFloat(style.paddingLeft) - parseFloat(style.paddingRight);
        }
    };

    @action setTabsContainerWrapperWidth = () => {
        if (!this.tabsContainerWrapperRef) {
            return;
        }

        const width = this.tabsContainerWrapperRef.offsetWidth;
        if (this.tabsContainerWrapperWidth !== width) {
            this.tabsContainerWrapperWidth = width;
        }
    };

    @action setTabsContainerWidth = () => {
        if (!this.tabsContainerRef) {
            return;
        }

        const width = this.tabsContainerRef.offsetWidth;
        if (this.tabsContainerWidth !== width) {
            this.tabsContainerWidth = width;
        }
    };

    @action updateTabWidths = () => {
        this.tabRefs.forEach((ref, key) => {
            if (!ref) {
                return;
            }

            const width = ref.offsetWidth;
            if (this.tabWidths.get(key) !== width) {
                this.tabWidths.set(key, width);
            }
        });
    };

    setDimensions = () => {
        this.setTabsWidth();
        this.setTabsContainerWrapperWidth();
        this.setTabsContainerWidth();
        this.updateTabWidths();
    };

    @action setTabRef = (index: number, ref: ?ElementRef<'li'>) => {
        if (this.tabRefs.get(index) !== ref) {
            this.tabRefs.set(index, ref);
        }
    };

    @action handleDropdownToggle = () => {
        this.dropdownOpen = !this.dropdownOpen;
    };

    @action handleDropdownClose = () => {
        this.dropdownOpen = false;
    };

    changeTab = (selectedTabIndex: ?number) => {
        if (selectedTabIndex !== undefined && selectedTabIndex !== null) {
            this.props.onSelect(selectedTabIndex);
        }
    };

    handleTabClick = (selectedTabIndex: ?number) => {
        this.changeTab(selectedTabIndex);
    };

    @action handleCollapsedTabClick = (selectedTabIndex: number) => {
        this.dropdownOpen = false;
        this.lastShownIndex = selectedTabIndex;

        this.changeTab(selectedTabIndex);
    };

    isSelected(tabIndex: number) {
        return tabIndex === this.props.selectedIndex;
    }

    get childIndices(): number[] {
        const {children} = this.props;

        return React.Children.map(children, (child, index) => index);
    }

    @computed get visibleTabIndices(): number[] {
        if (this.tabsContainerWidth <= this.tabsWidth) {
            return this.childIndices;
        }

        const {selectedIndex} = this.props;

        let visibleWidth = 0;
        let visibleTabIndices: number[] = [];

        const childIndices = this.childIndices;

        // Sorts childIndices in it's natural order, except that the element with selectedIndex is positioned at the
        // first place and the element withlastShownIndex is positioned at the second place.
        // This ensures that those two elements will always be visible.
        childIndices.sort((a, b) => {
            if (a === selectedIndex) {
                return -1;
            }

            if (b === selectedIndex) {
                return 1;
            }

            if (a === this.lastShownIndex) {
                return -1;
            }

            if (b === this.lastShownIndex) {
                return 1;
            }

            return a - b;
        });

        for (const index of childIndices) {
            const nextWidth = this.tabWidths.get(index);

            if (undefined === nextWidth) {
                if (visibleTabIndices.length > 0) {
                    break;
                }

                return this.childIndices;
            }

            if (visibleWidth + nextWidth > this.tabsContainerWrapperWidth) {
                break;
            }

            visibleWidth += nextWidth;
            visibleTabIndices = [...visibleTabIndices, index];
        }

        // Since visibleTabIndices contains only the indices of the elements that can be fully shown, we need to reset
        // the sorting so the elements have the correct order again.
        visibleTabIndices.sort((a, b) => a - b);

        return visibleTabIndices;
    }

    @computed get collapsedTabIndices(): number[] {
        const visibleTabIndices = this.visibleTabIndices;

        return this.childIndices.filter((index) => !visibleTabIndices.includes(index));
    }

    @computed get hasCollapsedTabs(): boolean {
        return this.collapsedTabIndices.length > 0;
    }

    createTabItems(tabs: Array<Element<typeof Tab> | false>, indices: number[], hidden: boolean) {
        const {small} = this.props;

        return React.Children.map(tabs, (tab, localIndex) => {
            const index = indices[localIndex];

            if (!tab) {
                return null;
            }

            const selected = this.isSelected(index);

            return React.cloneElement(
                tab,
                {
                    ...tab.props,
                    hidden,
                    index,
                    selected,
                    small,
                    onClick: this.handleTabClick,
                    tabRef: this.setTabRef,
                }
            );
        });
    }

    createTabs = () => {
        const {children} = this.props;
        const {visibleTabIndices, collapsedTabIndices} = this;

        return [
            ...this.createTabItems(
                React.Children.toArray(children).filter(
                    (child, index) => visibleTabIndices.includes(index)
                ),
                visibleTabIndices,
                false
            ),
            ...this.createTabItems(
                React.Children.toArray(children).filter(
                    (child, index) => collapsedTabIndices.includes(index)
                ),
                collapsedTabIndices,
                true
            ),
        ];
    };

    createCollapsedTabItems(tabs: Array<Element<typeof Tab> | false>, indices: number[]) {
        return React.Children.map(tabs, (tab, localIndex) => {
            const index = indices[localIndex];

            if (!tab) {
                return null;
            }

            return (
                <CollapsedTab
                    index={index}
                    key={index}
                    onClick={this.handleCollapsedTabClick}
                >
                    {tab.props.children}
                </CollapsedTab>
            );
        });
    }

    createCollapsedTabs = () => {
        const {children} = this.props;
        const {collapsedTabIndices} = this;

        return this.createCollapsedTabItems(
            React.Children.toArray(children).filter((child, index) => collapsedTabIndices.includes(index)),
            collapsedTabIndices
        );
    };

    render() {
        const {
            skin,
            small,
        } = this.props;

        const tabsClass = classNames(
            tabsStyles.tabs,
            tabsStyles[skin],
            {
                [tabsStyles.small]: small,
            }
        );

        return (
            <div className={tabsClass} ref={this.setTabsRef}>
                <div className={tabsStyles.tabsContainerWrapper} ref={this.setTabsContainerWrapperRef}>
                    <ul className={tabsStyles.tabsContainer} ref={this.setTabsContainerRef}>
                        {this.createTabs()}
                    </ul>
                </div>

                {this.hasCollapsedTabs &&
                    <React.Fragment>
                        <button
                            className={tabsStyles.button}
                            onClick={this.handleDropdownToggle}
                        >
                            <Icon name="su-more-horizontal" />
                        </button>

                        <Popover
                            anchorElement={this.tabsRef || undefined}
                            horizontalOffset={99999999} // just an extremely high value to keep the tab aligned right
                            onClose={this.handleDropdownClose}
                            open={this.dropdownOpen}
                        >
                            {
                                (setPopoverRef, styles) => (
                                    <div ref={setPopoverRef} style={styles}>
                                        <CollapsedTabList skin={skin}>
                                            {this.createCollapsedTabs()}
                                        </CollapsedTabList>
                                    </div>
                                )
                            }
                        </Popover>
                    </React.Fragment>
                }
            </div>
        );
    }
}

export default Tabs;
