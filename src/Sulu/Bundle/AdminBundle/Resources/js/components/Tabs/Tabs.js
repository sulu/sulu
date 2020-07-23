// @flow
import React, {Fragment} from 'react';
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

const TabWrapper = ({children, index, setWidth}: {
    children: Element<typeof Tab> | false,
    index: number,
    setWidth: (index: number, width: number) => void,
}) => {
    function setRef(ref: ?ElementRef<'div'>) {
        if (ref) {
            setWidth(index, ref.offsetWidth);
        }
    }

    if (!children) {
        return null;
    }

    return (
        <div ref={setRef}>
            {children}
        </div>
    );
};

@observer
class Tabs extends React.Component<Props> {
    @observable wrapperWidth: number = 0;
    @observable tabWidths: Map<number, number> = new Map();
    @observable dropdownOpen = false;
    @observable lastSelectedIndex: ?number;

    static defaultProps = {
        skin: 'default',
        small: false,
    };

    static Tab = Tab;

    containerRef: ?ElementRef<'div'>;
    wrapperRef: ?ElementRef<'div'>;

    componentDidMount() {
        this.setDimensions();

        // $FlowFixMe
        const resizeObserver = new ResizeObserver(
            debounce(() => {
                this.setDimensions();
            }, DEBOUNCE_TIME)
        );

        if (!this.wrapperRef) {
            return;
        }

        resizeObserver.observe(this.wrapperRef);
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

    @action setDimensions = () => {
        if (this.wrapperRef && this.wrapperWidth !== this.wrapperRef.offsetWidth) {
            this.wrapperWidth = this.wrapperRef.offsetWidth;
        }
    };

    @action setTabWidth = (index: number, width: number) => {
        this.tabWidths.set(index, width);
    };

    @action handleDropdownToggle = () => {
        this.dropdownOpen = !this.dropdownOpen;
    };

    @action handleDropdownClose = () => {
        this.dropdownOpen = false;
    };

    changeTab = (selectedTabIndex: ?number) => {
        if (typeof selectedTabIndex !== 'undefined' && selectedTabIndex !== null) {
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

            if (visibleWidth + nextWidth > this.wrapperWidth) {
                break;
            }

            visibleWidth += nextWidth;
            visibleTabIndices = [...visibleTabIndices, index];
        }

        visibleTabIndices.sort((a, b) => a - b);

        return visibleTabIndices;
    }

    get visibleTabs() {
        const {children} = this.props;
        const visibleTabIndices = this.visibleTabIndices;

        return this.createTabItems(
            React.Children.toArray(children).filter(
                (child, index) => visibleTabIndices.includes(index)
            ),
            visibleTabIndices
        );
    }

    @computed get hiddenTabIndices(): number[] {
        const visibleTabIndices = this.visibleTabIndices;

        return this.childIndices.filter((index) => !visibleTabIndices.includes(index));
    }

    @computed get hasHiddenTabs(): boolean {
        return this.hiddenTabIndices.length > 0;
    }

    get hiddenTabs() {
        const {children} = this.props;
        const hiddenTabIndices = this.hiddenTabIndices;

        return this.createCollapsedTabItems(
            React.Children.toArray(children).filter((child, index) => hiddenTabIndices.includes(index)),
            hiddenTabIndices
        );
    }

    createTabItems(tabs: Array<Element<typeof Tab> | false>, indices: number[]) {
        const {small} = this.props;

        return React.Children.map(tabs, (tab, localIndex) => {
            const index = indices[localIndex];

            if (!tab) {
                return null;
            }

            const selected = this.isSelected(index);

            return (
                <TabWrapper index={index} setWidth={this.setTabWidth}>
                    {React.cloneElement(
                        tab,
                        {
                            ...tab.props,
                            index,
                            selected,
                            small,
                            onClick: this.handleTabClick,
                        }
                    )}
                </TabWrapper>
            );
        });
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

        return (
            <div className={containerClass} ref={this.setContainerRef}>
                <div className={tabsStyles.wrapper} ref={this.setWrapperRef}>
                    <ul className={tabsStyles.tabs}>
                        {this.visibleTabs}
                    </ul>
                </div>

                {this.hasHiddenTabs &&
                    <Fragment>
                        <button
                            className={tabsStyles.button}
                            onClick={this.handleDropdownToggle}
                        >
                            <Icon name="su-more" />
                        </button>

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
                                            {this.hiddenTabs}
                                        </CollapsedTabList>
                                    </div>
                                )
                            }
                        </Popover>
                    </Fragment>
                }
            </div>
        );
    }
}

export default Tabs;
