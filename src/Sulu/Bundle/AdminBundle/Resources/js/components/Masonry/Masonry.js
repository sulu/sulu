// @flow
import React from 'react';
import imagesLoaded from 'imagesloaded';
import MasonryLayout from 'masonry-layout';
import masonryStyles from './masonry.scss';
import type {ChildrenArray, Node, ElementRef} from 'react';

const MASONRY_OPTIONS = {
    gutter: 30,
    transitionDuration: 250,
};

type Props = {
    children?: ChildrenArray<Node>,
};

export default class Masonry extends React.PureComponent<Props> {
    masonryRef: ?ElementRef<'ul'>;

    masonry: typeof MasonryLayout;

    layoutedChildNodes: HTMLElement[];

    componentDidMount() {
        this.initMasonryLayout();
        this.handleImagesLoading();
    }

    componentWillUnmount() {
        this.layoutedChildNodes = [];

        this.destroyMasonry();
    }

    componentDidUpdate() {
        this.handleChildrenUpdates();
        this.handleImagesLoading();
    }

    setMasonryRef = (ref: ?ElementRef<'ul'>) => {
        this.masonryRef = ref;
    };

    getChildNodes(): Array<*> {
        const {masonryRef} = this;

        if (!masonryRef) {
            return [];
        }

        const childNodes = masonryRef.children;

        return Array.from(childNodes);
    }

    initMasonryLayout() {
        this.masonry = new MasonryLayout(
            this.masonryRef,
            MASONRY_OPTIONS
        );

        this.layoutedChildNodes = this.getChildNodes();
    }

    destroyMasonry() {
        if (this.masonry) {
            this.masonry.destroy();
            this.masonry = null;
        }
    }

    cloneItems(originalItems: ?ChildrenArray<*>) {
        const itemStyle = {marginBottom: MASONRY_OPTIONS.gutter};

        return React.Children.map(originalItems, (item) => (
            <li style={itemStyle}>
                {
                    React.cloneElement(
                        item,
                        {
                            key: item.key,
                        }
                    )
                }
            </li>
        ));
    }

    handleChildrenUpdates() {
        const currentChildNodes = this.getChildNodes();
        const knownChildNodes = currentChildNodes.filter((currentChildNode) => {
            return this.layoutedChildNodes.includes(currentChildNode);
        });

        const newChildNodes = currentChildNodes.filter((currentChildNode) => {
            return !knownChildNodes.includes(currentChildNode);
        });

        const removedChildNodes = knownChildNodes.filter((knownChildNode) => {
            return !currentChildNodes.includes(knownChildNode);
        });

        let startIndex = 0;
        const prependedChildNodes = newChildNodes.filter((newChildNode) => {
            const isPrepended = (startIndex === currentChildNodes.indexOf(newChildNode));

            if (isPrepended) {
                startIndex++;
            }

            return isPrepended;
        });

        const appendedChildNodes = newChildNodes.filter((newChildNode) => {
            return !prependedChildNodes.includes(newChildNode);
        });

        if (removedChildNodes.length > 0) {
            this.masonry.remove(removedChildNodes);
        }

        if (appendedChildNodes.length > 0) {
            this.masonry.appended(appendedChildNodes);
        }

        if (prependedChildNodes.length > 0) {
            this.masonry.prepended(prependedChildNodes);
        }

        this.layoutedChildNodes = currentChildNodes;

        if (
            removedChildNodes.length > 0 ||
            appendedChildNodes.length > 0 ||
            prependedChildNodes.length > 0
        ) {
            this.masonry.reloadItems();
        }

        this.masonry.layout();
    }

    handleImagesLoading() {
        imagesLoaded(this.layoutedChildNodes).once('always', () => {
            if (this.masonry) {
                this.masonry.layout();
            }
        });
    }

    render() {
        const {
            children,
        } = this.props;
        const clonedItems = this.cloneItems(children);

        return (
            <ul
                className={masonryStyles.masonry}
                ref={this.setMasonryRef}
            >
                {clonedItems}
            </ul>
        );
    }
}
