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
    buttons?: Array<ItemButtonConfig>,
    children: ChildrenArray<Element<typeof Column>>,
    onItemClick: (id: string | number) => void,
    toolbarItems: Array<ToolbarItemConfig>,
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

    componentWillReceiveProps(nextProps: Props) {
        if (this.container && nextProps.children !== this.props.children) {
            this.container.scrollLeft = this.columnWidth * (nextProps.children.length - 1);
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
                    buttons: this.props.buttons,
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
                <div className={columnListContainerClass} ref={this.setContainerRef}>
                    <div className={columnListStyles.columnList}>
                        {this.cloneColumns(children)}
                    </div>
                </div>
            </div>
        );
    }
}
