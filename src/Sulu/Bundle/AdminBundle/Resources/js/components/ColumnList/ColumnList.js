// @flow
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import React from 'react';
import type {ChildrenArray, Element, ElementRef} from 'react';
import classNames from 'classnames';
import Column from './Column';
import Item from './Item';
import Toolbar from './Toolbar';
import type {ItemButtonConfig, ToolbarItemConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {
    children: ChildrenArray<Element<typeof Column>>,
    buttons?: Array<ItemButtonConfig>,
    toolbarItems: Array<ToolbarItemConfig>,
    onItemClick: (id: string | number) => void,
};

@observer
export default class ColumnList extends React.Component<Props> {
    static defaultProps = {
        toolbarItems: [],
    };

    static Column = Column;

    static Item = Item;

    @observable activeColumnIndex: number = 0;
    @observable scrollPosition: number = 0;

    container: ElementRef<'div'>;
    toolbar: ElementRef<'div'>;

    setContainerRef = (ref: ?ElementRef<'div'>) => {
        if (!ref) {
            return;
        }

        this.container = ref;
    };

    setToolbarRef = (ref: ?ElementRef<'div'>) => {
        if (!ref) {
            return;
        }

        this.toolbar = ref;
    };

    componentDidMount() {
        this.container.addEventListener('scroll', this.handleScroll);
    }

    componentWillUnmount() {
        this.container.removeEventListener('scroll', this.handleScroll);
    }

    get toolbarWidth(): number {
        if (!this.toolbar) {
            return 0;
        }

        // remove the 1px border from the toolbar to get the correct width
        return this.toolbar.getBoundingClientRect().width - 1;
    }

    get containerWidth(): number {
        if (!this.container) {
            return 0;
        }

        return this.container.clientWidth;
    }

    get containerScrollWidth(): number {
        if (!this.container) {
            return 0;
        }

        return this.container.scrollWidth;
    }

    get containerScrolling(): boolean {
        return this.containerWidth < this.containerScrollWidth;
    }

    @action handleScroll = () => {
        this.scrollPosition = this.container.scrollLeft;
    };

    @action handleActive = (index?: number) => {
        if (index === undefined) {
            return;
        }

        this.activeColumnIndex = index;
    };

    cloneColumns = (originalColumns: ChildrenArray<Element<typeof Column>>) => {
        const {onItemClick} = this.props;

        return React.Children.map(originalColumns, (column, index) => {
            return React.cloneElement(
                column,
                {
                    index: index,
                    buttons: this.props.buttons,
                    onActive: this.handleActive,
                    onItemClick: onItemClick,
                    scrolling: this.containerScrolling,
                }
            );
        });
    };

    render() {
        const {children, toolbarItems} = this.props;
        const toolbarPosition = -this.scrollPosition + this.activeColumnIndex * this.toolbarWidth;

        const columnListContainerClass = classNames(
            columnListStyles.columnListContainer,
            {
                [columnListStyles.firstVisibleColumnActive]: toolbarPosition <= 0,
                [columnListStyles.lastVisibleColumnActive]: toolbarPosition >= this.containerWidth - this.toolbarWidth,
            }
        );

        return (
            <div className={columnListStyles.columnListToolbarContainer}>
                <div style={{marginLeft: toolbarPosition}}>
                    <Toolbar
                        columnIndex={this.activeColumnIndex}
                        toolbarItems={toolbarItems}
                        toolbarRef={this.setToolbarRef}
                    />
                </div>
                <div ref={this.setContainerRef} className={columnListContainerClass}>
                    <div className={columnListStyles.columnList}>
                        {this.cloneColumns(children)}
                    </div>
                </div>
            </div>
        );
    }
}
