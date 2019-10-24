// @flow
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import React from 'react';
import type {ChildrenArray, Element, ElementRef} from 'react';
import classNames from 'classnames';
import Column from './Column';
import Item from './Item';
import Toolbar from './Toolbar';
import type {ToolbarItemConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {|
    children: ChildrenArray<Element<typeof Column>>,
    onItemClick: (id: string | number) => void,
    onItemDoubleClick?: ?(id: string | number) => void,
    toolbarItemsProvider: (index: number) => ?Array<ToolbarItemConfig>,
|};

@observer
class ColumnList extends React.Component<Props> {
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

    @action componentDidUpdate(prevProps: Props) {
        const {children} = this.props;
        if (this.activeColumnIndex >= React.Children.count(children)) {
            this.activeColumnIndex = 0;
        }

        if (this.container && this.props.children !== prevProps.children) {
            this.container.scrollLeft = this.columnWidth * (React.Children.count(children) - 1);
        }
    }

    get columnWidth(): number {
        const columnWidth = parseInt(columnListStyles.columnWidth);

        if (isNaN(columnWidth)) {
            return 0;
        }

        return columnWidth;
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
        const {onItemClick, onItemDoubleClick} = this.props;
        const scrolling = this.containerScrolling;

        return React.Children.map(originalColumns, (column, index) => {
            return React.cloneElement(
                column,
                {
                    index: index,
                    onActive: this.handleActive,
                    onItemClick: onItemClick,
                    onItemDoubleClick: onItemDoubleClick,
                    scrolling,
                }
            );
        });
    };

    render() {
        const {children} = this.props;
        const toolbarPosition = -this.scrollPosition + this.activeColumnIndex * this.columnWidth;

        const columnListContainerClass = classNames(
            columnListStyles.columnListContainer,
            {
                [columnListStyles.firstVisibleColumnActive]: toolbarPosition <= 0,
                [columnListStyles.lastVisibleColumnActive]: toolbarPosition >= this.containerWidth - this.columnWidth,
            }
        );

        const toolbarItems = this.props.toolbarItemsProvider(this.activeColumnIndex);

        return (
            <div className={columnListStyles.columnListToolbarContainer}>
                {!!toolbarItems &&
                    <div className={columnListStyles.toolbarContainer} style={{marginLeft: toolbarPosition}}>
                        {!!toolbarItems.length &&
                            <Toolbar
                                toolbarItems={toolbarItems}
                                toolbarRef={this.setToolbarRef}
                            />
                        }
                    </div>
                }
                <div className={columnListContainerClass} ref={this.setContainerRef}>
                    <div className={columnListStyles.columnList}>
                        {this.cloneColumns(children)}
                    </div>
                </div>
            </div>
        );
    }
}

export default ColumnList;
