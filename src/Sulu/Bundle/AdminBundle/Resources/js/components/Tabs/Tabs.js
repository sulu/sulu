// @flow
import React from 'react';
import type {ChildrenArray, Element, ElementRef} from 'react';
import classNames from 'classnames';
import {action, computed, observable} from 'mobx';
import debounce from 'debounce';
import {observer} from 'mobx-react';
import Popover from '../Popover';
import Menu from '../Menu';
import Option from '../Select/Option';
import type {Skin} from './types';
import Tab from './Tab';
import tabsStyles from './tabs.scss';

type Props = {
    children: ChildrenArray<Element<typeof Tab> | false>,
    onSelect: (tabIndex: number) => void,
    selectedIndex: ?number,
    skin: Skin,
};

const DEBOUNCE_TIME = 200;

const TabWrapper = ({children, index, setWidth}) => {
    function setRef(ref: ?ElementRef<'div'>) {
        if (ref) {
            setWidth(index, ref.offsetWidth);
        }
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

    static defaultProps = {
        skin: 'default',
    };

    static Tab = Tab;

    wrapperRef: ?ElementRef<'div'>;
    listRef: ?ElementRef<'ul'>;
    buttonRef: ?ElementRef<*>;

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

    setWrapperRef = (ref: ?ElementRef<'div'>) => {
        this.wrapperRef = ref;
    };

    setListRef = (ref: ?ElementRef<'ul'>) => {
        this.listRef = ref;
    };

    setMoreButtonRef = (ref: ?ElementRef<*>) => {
        this.buttonRef = ref;
    };

    @action setTabWidth = (index: number, width: number) => {
        this.tabWidths.set(index, width);
    };

    @action handleMoreButtonClick = () => {
        this.dropdownOpen = !this.dropdownOpen;
    };

    @action handleDropdownClose = () => {
        this.dropdownOpen = false;
    };

    @action setDimensions = () => {
        if (this.wrapperRef && this.wrapperWidth !== this.wrapperRef.offsetWidth) {
            this.wrapperWidth = this.wrapperRef.offsetWidth;
        }
    };

    @action handleSelectionChange = (selectedTabIndex: ?number) => {
        this.dropdownOpen = false;

        if (typeof selectedTabIndex !== 'undefined' && selectedTabIndex !== null) {
            this.props.onSelect(selectedTabIndex);
        }
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

        return this.createTabItems(
            React.Children.toArray(children).filter((child, index) => hiddenTabIndices.includes(index)),
            hiddenTabIndices
        );
    }

    createTabItems(tabs: Array<Element<typeof Tab> | false>, indices: number[]) {
        const {skin} = this.props;

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
                            skin,
                            onClick: this.handleSelectionChange,
                        }
                    )}
                </TabWrapper>
            );
        });
    }

    render() {
        const {
            skin,
        } = this.props;

        const containerClass = classNames(
            tabsStyles.container,
            {
                [tabsStyles.compact]: skin === 'compact',
            }
        );

        return (
            <>
                <div className={containerClass}>
                    <div className={tabsStyles.wrapper} ref={this.setWrapperRef}>
                        <ul className={tabsStyles.tabs} ref={this.setListRef}>
                            {this.visibleTabs}
                        </ul>
                    </div>

                    {this.hasHiddenTabs &&
                        <>
                            <button onClick={this.handleMoreButtonClick} ref={this.setMoreButtonRef}>
                                ...
                            </button>

                            <Popover
                                anchorElement={this.buttonRef ? this.buttonRef : undefined}
                                backdrop={true}
                                onClose={this.handleDropdownClose}
                                open={this.dropdownOpen}
                            >
                                {
                                    (setPopoverRef, styles) => (
                                        <Menu menuRef={setPopoverRef} style={styles}>
                                            {this.hiddenTabs.map((child, localIndex) => {
                                                const index = this.hiddenTabIndices[localIndex];

                                                return (
                                                    <Option
                                                        key={index}
                                                        onClick={this.handleSelectionChange}
                                                        value={index}
                                                    >
                                                        {child.props.children.props.children}
                                                    </Option>
                                                );
                                            })}
                                        </Menu>
                                    )
                                }
                            </Popover>
                        </>
                    }
                </div>
            </>
        );
    }
}

export default Tabs;
