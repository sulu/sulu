// @flow
import type {ChildrenArray, ElementRef} from 'react';
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable, computed} from 'mobx';
import classNames from 'classnames';
import debounce from 'debounce';
import type {Item, Skin} from './types';
import itemsStyles from './items.scss';

type Props = {
    children: ChildrenArray<Item>,
    skin?: Skin,
};

const DEBOUNCE_TIME = 200;

@observer
class Items extends React.Component<Props> {
    @observable expandedWidth: number = 0;
    @observable parentWidth: number = 0;

    static defaultProps = {
        skin: 'light',
    };

    parentRef: ?ElementRef<'div'>;
    childRef: ?ElementRef<'ul'>;

    setParentRef = (ref: ?ElementRef<'div'>) => {
        this.parentRef = ref;
    };

    setChildRef = (ref: ?ElementRef<'ul'>) => {
        this.childRef = ref;
    };

    componentDidMount() {
        this.setDimensions();

        // $FlowFixMe
        const resizeObserver = new ResizeObserver(
            debounce(() => {
                this.setDimensions();
            }, DEBOUNCE_TIME)
        );

        if (!this.parentRef) {
            return;
        }

        resizeObserver.observe(this.parentRef);
    }

    @action componentDidUpdate() {
        if (this.parentRef && this.parentWidth !== this.parentRef.offsetWidth) {
            this.parentWidth = this.parentRef.offsetWidth;
        }

        if (this.childRef && this.showText && this.expandedWidth !== this.childRef.offsetWidth) {
            this.expandedWidth = this.childRef.offsetWidth;
        }
    }

    @action setDimensions = () => {
        const {parentRef, childRef} = this;

        if (childRef && (this.showText || childRef.offsetWidth > this.expandedWidth)) {
            this.expandedWidth = childRef.offsetWidth;
        }

        if (!parentRef) {
            return;
        }

        this.parentWidth = parentRef.offsetWidth;
    };

    @computed get showText(): boolean {
        return this.parentWidth >= this.expandedWidth;
    }

    render() {
        const {skin, children} = this.props;

        const itemsClass = classNames(itemsStyles.items, itemsStyles[skin]);

        return (
            <div className={itemsStyles.itemsContainer} ref={this.setParentRef}>
                <ul className={itemsClass} ref={this.setChildRef}>
                    {children &&
                        React.Children.map(children, (item, index) => (
                            <li key={index}>
                                {React.cloneElement(item, {
                                    ...item.props,
                                    showText: this.showText,
                                    skin: skin,
                                })}
                            </li>
                        ))
                    }
                </ul>
            </div>
        );
    }
}

export default Items;
