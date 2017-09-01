// @flow
import type {ChildrenArray, Node, ElementRef} from 'react';
import React from 'react';
import imagesLoaded from 'imagesloaded';
import MasonryLayout from 'masonry-layout';
import masonryStyles from './masonry.scss';

type Props = {
    children?: ChildrenArray<*>,
    /** Called when an item gets selected or deselected */
    onItemClick?: (rowId: string | number) => void,
    /** Called when an item gets selected or deselected */
    onItemSelectionChange?: (rowId: string | number, checked: boolean) => void,
    /** 
     * Specifies which child elements will be used as item elements in the layout. 
     * Should always be set cause of performance reasons. 
     */
    itemSelector?: string,
    /** The time it takes for every animation. Set to "0" to disable animations */
    transitionDuration: number,
    /** Adds horizontal space between item. To set vertical space between elements, use margin in CSS */
    gutter: number,
};

export default class Masonry extends React.PureComponent<Props> {
    static defaultProps = {
        gutter: 15,
        transitionDuration: 250,
    };

    elementRef: ElementRef<'div'>;

    masonry: MasonryLayout;

    latestKnownChildNodes: Node[];

    componentDidMount() {
        this.initMasonryLayout();
        this.handleImagesLoading();
    }

    componentWillUnmount() {
        this.latestKnownChildNodes = [];

        this.destroyMasonry();
    }

    componentDidUpdate() {
        this.handleChildrenUpdates();
        this.handleImagesLoading();
    }

    setLayoutElementRef = (ref: ElementRef<'ul'>) => {
        this.elementRef = ref;
    };

    getChildNodes() {
        const containerNode = this.elementRef;
        const childNodes = containerNode.children;

        return Array.from(childNodes);
    }

    initMasonryLayout() {
        const {
            gutter,
            itemSelector,
            transitionDuration,
        } = this.props;
        const options = {
            gutter,
            itemSelector,
            transitionDuration,
            horizontalOrder: true,
        };

        this.masonry = new MasonryLayout(
            this.elementRef,
            options,
        );

        this.latestKnownChildNodes = this.getChildNodes();
    }

    destroyMasonry() {
        if (this.masonry) {
            this.masonry.destroy();

            this.masonry = null;
        }
    }

    cloneItems(originalItems: any) {
        return React.Children.map(originalItems, (item) => {
            return React.cloneElement(
                item,
                {
                    key: item.key,
                    onClick: this.handleItemClick,
                    onSelectionChange: this.handleItemSelect,
                },
            );
        });
    }

    handleChildrenUpdates() {
        const currentChildNodes = this.getChildNodes();
        const knownChildNodes = currentChildNodes.filter((currentChildNode) => {
            return this.latestKnownChildNodes.includes(currentChildNode);
        });

        const newChildNodes = currentChildNodes.filter((currentChildNode) => {
            return !knownChildNodes.includes(currentChildNode);
        });

        const removedChildNodes = knownChildNodes.filter((knownChildNode) => {
            return !currentChildNodes.includes(knownChildNode);
        });

        let beginningIndex = 0;
        const prependedChildNodes = newChildNodes.filter((newChildNode) => {
            const isPrepended = (beginningIndex === currentChildNodes.indexOf(newChildNode));

            if (isPrepended) {
                beginningIndex++;
            }

            return isPrepended;
        });

        const appendedChildNodes = newChildNodes.filter((newChildNode) => {
            return !prependedChildNodes.includes(newChildNode);
        });

        if (removedChildNodes.length > 0) {
            this.masonry.remove(removedChildNodes);
            this.masonry.reloadItems();
        }

        if (appendedChildNodes.length > 0) {
            this.masonry.appended(appendedChildNodes);
            this.masonry.reloadItems();

            if (prependedChildNodes.length === 0) {
                this.masonry.reloadItems();
            }
        }

        if (prependedChildNodes.length > 0) {
            this.masonry.prepended(prependedChildNodes);
            this.masonry.reloadItems();
        }

        this.latestKnownChildNodes = currentChildNodes;

        this.masonry.layout();
    }

    handleImagesLoading() {
        imagesLoaded(this.latestKnownChildNodes)
            .once('always', () => this.masonry.layout());
    }

    handleItemClick = (itemId: string | number) => {
        if (this.props.onItemClick) {
            this.props.onItemClick(itemId);
        }
    };

    handleItemSelect = (itemId: string | number, checked: boolean) => {
        if (this.props.onItemSelectionChange) {
            this.props.onItemSelectionChange(itemId, checked);
        }
    };

    render() {
        const {
            children,
        } = this.props;
        const clonedItems = this.cloneItems(children);

        return (
            <div
                ref={this.setLayoutElementRef}
                className={masonryStyles.masonry}>
                {clonedItems}
            </div>
        );
    }
}
