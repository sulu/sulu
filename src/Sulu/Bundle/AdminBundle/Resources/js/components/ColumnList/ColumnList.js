// @flow
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import React from 'react';
import type {ChildrenArray, Element, ElementRef} from 'react';
import classNames from 'classnames';
import Column from './Column';
import Item from './Item';
import Toolbar from './Toolbar';
import type { ToolbarItemConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {|
    children: ChildrenArray<Element<typeof Column>>,
    toolbarItems: Array<ToolbarItemConfig>,
    onItemClick: (id: string | number) => void,
|};

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
        const {onItemClick} = this.props;
        const scrolling = this.containerScrolling;

        return React.Children.map(originalColumns, (column, index) => {
            return React.cloneElement(
                column,
                {
                    index: index,
                    onActive: this.handleActive,
                    onItemClick: onItemClick,
                    scrolling,
                }
            );
        });
    };

    render() {
        const {children, toolbarItems} = this.props;
        const toolbarPosition = -this.scrollPosition + this.activeColumnIndex * this.columnWidth;

        const columnListContainerClass = classNames(
            columnListStyles.columnListContainer,
            {
                [columnListStyles.firstVisibleColumnActive]: toolbarPosition <= 0,
                [columnListStyles.lastVisibleColumnActive]: toolbarPosition >= this.containerWidth - this.columnWidth,
            }
        );

        return (
            <div className={columnListStyles.columnListToolbarContainer}>
                {!!toolbarItems.length &&
                    <div className={columnListStyles.toolbarContainer} style={{marginLeft: toolbarPosition}}>
                        <Toolbar
                            columnIndex={this.activeColumnIndex}
                            toolbarItems={toolbarItems}
                            toolbarRef={this.setToolbarRef}
                        />
                    </div>
                }
                <div ref={this.setContainerRef} className={columnListContainerClass}>
                    <div className={columnListStyles.columnList}>
                        {this.cloneColumns(children)}
                    </div>
                </div>
            </div>
        );
    }
}
