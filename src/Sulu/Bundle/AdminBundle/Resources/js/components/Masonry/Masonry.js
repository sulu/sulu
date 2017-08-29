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
};

type Props = {
    children?: any,
};

export default class Masonry extends React.PureComponent<Props> {
    elementRef: ElementRef<'ul'>;

    masonry: MasonryLayout;

    componentDidMount() {
        this.initMasonryLayout();
    }

    componentWillUnmount() {
        if (this.masonry) {
            this.masonry.destroy();

            this.masonry = null;
        }
    }

    componentDidUpdate(prevProps: Props) {
        // const knownDOMChildren = React.children.map();
        React.Children.map(prevProps.children, (child) => {
            console.log(child.ref);
        });
    }

    initMasonryLayout() {
        this.masonry = new MasonryLayout(
            this.elementRef,
            masonryDefaultOptions,
        );
    }

    setLayoutElementRef = (ref: ElementRef<'ul'>) => {
        this.elementRef = ref;
    };

    render() {
        const {
            children,
        } = this.props;

        return (
            <ul
                ref={this.setLayoutElementRef}
                className={masonryStyles.masonry}>
                {
                    children.map((child, index) => {
                        return (
                            <li key={index} ref={(ref) => {console.log(ref);}}>
                                {child.props.children}
                            </li>
                        );
                    })
                }
            </ul>
        );
    }
}
