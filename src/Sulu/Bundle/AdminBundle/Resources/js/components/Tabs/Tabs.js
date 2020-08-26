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

    @observable wrapperWidth: number = 0;
    @observable tabsWrapperWidth: number = 0;
    @observable tabsWidth: number = 0;

    @observable tabWidths: Map<number, number> = new Map();
    @observable tabRefs: Map<number, ?ElementRef<'li'>> = new Map();
    @observable dropdownOpen = false;
    @observable lastSelectedIndex: ?number;

    static Tab = Tab;

    resizeObserver: ?ResizeObserver;

    containerRef: ?ElementRef<'div'>;
    wrapperRef: ?ElementRef<'div'>;
    tabsWrapperRef: ?ElementRef<'div'>;
    tabsRef: ?ElementRef<'ul'>;

    componentDidMount() {
        this.resizeObserver = new ResizeObserver(
            debounce(this.setDimensions, DEBOUNCE_TIME)
        );

        if (this.wrapperRef) {
            this.resizeObserver.observe(this.wrapperRef);
        }

        if (this.tabsWrapperRef) {
            this.resizeObserver.observe(this.tabsWrapperRef);
        }

        if (this.tabsRef) {
            this.resizeObserver.observe(this.tabsRef);
        }
    }

    componentWillUnmount() {
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
    }

    componentDidUpdate() {
        this.setDimensions();
    }

    setContainerRef = (ref: ?ElementRef<'div'>) => {
        this.containerRef = ref;
    };

    setWrapperRef = (ref: ?ElementRef<'div'>) => {
        this.wrapperRef = ref;
    };

    setTabsWrapperRef = (ref: ?ElementRef<'div'>) => {
        this.tabsWrapperRef = ref;
    };

    setTabsRef = (ref: ?ElementRef<'ul'>) => {
        this.tabsRef = ref;
    };

    @action setWrapperWidth = () => {
        if (!this.wrapperRef) {
            return;
        }

        const width = this.wrapperRef.offsetWidth;
        if (this.wrapperWidth !== width) {
            this.wrapperWidth = width;
        }
    };

    @action setTabsWrapperWidth = () => {
        if (!this.tabsWrapperRef) {
            return;
        }

        const width = this.tabsWrapperRef.offsetWidth;
        if (this.tabsWrapperWidth !== width) {
            this.tabsWrapperWidth = width;
        }
    };

    @action setTabsWidth = () => {
        if (!this.tabsRef) {
            return;
        }

        const width = this.tabsRef.offsetWidth;
        if (this.tabsWidth !== width) {
            this.tabsWidth = width;
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
        this.setWrapperWidth();
        this.setTabsWrapperWidth();
        this.setTabsWidth();
        this.updateTabWidths();
    };

    @action handleTabRefChange = (index: number, ref: ?ElementRef<'li'>) => {
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
        this.lastSelectedIndex = selectedTabIndex;

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
        if (this.tabsWidth <= this.wrapperWidth) {
            return this.childIndices;
        }

        const {selectedIndex} = this.props;

        let visibleWidth = 0;
        let visibleTabIndices: number[] = [];

        const childIndices = this.childIndices;
        childIndices.sort((a, b) => {
            if (a === selectedIndex) {
                return -1;
            }

            if (b === selectedIndex) {
                return 1;
            }

            if (a === this.lastSelectedIndex) {
                return -1;
            }

            if (b === this.lastSelectedIndex) {
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

            if (visibleWidth + nextWidth > this.tabsWrapperWidth) {
                break;
            }

            visibleWidth += nextWidth;
            visibleTabIndices = [...visibleTabIndices, index];
        }

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

    get tabs() {
        const {children} = this.props;
        const visibleTabIndices = this.visibleTabIndices;
        const collapsedTabIndices = this.collapsedTabIndices;

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
                    onRefChange: this.handleTabRefChange,
                }
            );
        });
    }

    get collapsedTabs() {
        const {children} = this.props;
        const collapsedTabIndices = this.collapsedTabIndices;

        return this.createCollapsedTabItems(
            React.Children.toArray(children).filter((child, index) => collapsedTabIndices.includes(index)),
            collapsedTabIndices
        );
    }

    createCollapsedTabItems(tabs: Array<Element<typeof Tab> | false>, indices: number[]) {
        const {skin} = this.props;

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
                    skin={tab.props.skin || skin}
                >
                    {tab.props.children}
                </CollapsedTab>
            );
        });
    }

    render() {
        const {
            skin,
            small,
        } = this.props;

        const containerClass = classNames(
            tabsStyles.container,
            tabsStyles[skin],
            {
                [tabsStyles.small]: small,
            }
        );

        const buttonClass = classNames(
            tabsStyles.button,
            {
                [tabsStyles.hidden]: !this.hasCollapsedTabs,
            }
        );

        return (
            <div className={containerClass} ref={this.setContainerRef}>
                <div className={tabsStyles.wrapper} ref={this.setWrapperRef}>
                    <div className={tabsStyles.tabsWrapper} ref={this.setTabsWrapperRef}>
                        <ul className={tabsStyles.tabs} ref={this.setTabsRef}>
                            {this.tabs}
                        </ul>
                    </div>

                    <button
                        className={buttonClass}
                        onClick={this.handleDropdownToggle}
                    >
                        <Icon name="su-more-horizontal" />
                    </button>

                    {this.hasCollapsedTabs &&
                        <Popover
                            anchorElement={this.containerRef || undefined}
                            horizontalOffset={10000}
                            onClose={this.handleDropdownClose}
                            open={this.dropdownOpen}
                        >
                            {
                                (setPopoverRef, styles) => (
                                    <div ref={setPopoverRef} style={styles}>
                                        <CollapsedTabList skin={skin}>
                                            {this.collapsedTabs}
                                        </CollapsedTabList>
                                    </div>
                                )
                            }
                        </Popover>
                    }
                </div>
            </div>
        );
    }
}

export default Tabs;
