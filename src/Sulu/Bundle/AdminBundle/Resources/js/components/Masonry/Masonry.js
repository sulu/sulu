// @flow
import type {ChildrenArray, Element, ElementRef} from 'react';
import React from 'react';
import ReactDOM from 'react-dom';
import MasonryLayout from 'masonry-layout';
import MasonryItem from './MasonryItem';
import masonryStyles from './masonry.scss';

const masonryDefaultOptions = {
    horizontalOrder: true,
    itemSelector: 'li',
    transitionDuration: 250,
    columnWidth: 200,
    gutter: 10,
};

type Props = {
    children?: any,
};

export default class Masonry extends React.PureComponent<Props> {
    elementRef: ElementRef<'ul'>;

    masonry: MasonryLayout;

    latestKnownChildNodes: Element<'li'>;

    componentDidMount() {
        this.initMasonryLayout();
    }

    componentWillUnmount() {
        this.latestKnownChildNodes = null;

        if (this.masonry) {
            this.masonry.destroy();

            this.masonry = null;
        }
    }

    componentDidUpdate() {
        const currentChildNodes = this.getChildNodes();
        const knownChildNodes = [];
        const newChildNodes = [];

        currentChildNodes.forEach((childNode) => {
            if (this.latestKnownChildNodes.includes(childNode)) {
                knownChildNodes.push(childNode);
            } else {
                newChildNodes.push(childNode);
            }
        });

        this.masonry.appended(newChildNodes);

        this.latestKnownChildNodes = currentChildNodes;

        this.masonry.reloadItems();
        this.masonry.layout();
    }

    getChildNodes() {
        const containerNode = this.elementRef;
        const childNodes = containerNode.children;

        return Array.from(childNodes);
    }

    cloneItems(originalItems: any) {
        return React.Children.map(originalItems, (item, index) => {
            return React.cloneElement(
                item,
                {
                    key: index,
                },
            );
        });
    }

    initMasonryLayout() {
        this.masonry = new MasonryLayout(
            this.elementRef,
            masonryDefaultOptions,
        );

        this.latestKnownChildNodes = this.getChildNodes();
    }

    setLayoutElementRef = (ref: ElementRef<'ul'>) => {
        this.elementRef = ref;
    };

    render() {
        const {
            children,
        } = this.props;
        const clonedItems = this.cloneItems(children);

        return (
            <ul
                ref={this.setLayoutElementRef}
                className={masonryStyles.masonry}>
                {clonedItems}
            </ul>
        );
    }
}
