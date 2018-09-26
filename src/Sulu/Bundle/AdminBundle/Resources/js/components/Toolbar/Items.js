// @flow
import type {ChildrenArray} from 'react';
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import type {Item, Skin} from './types';
import itemsStyles from './items.scss';

type Props = {
    children: ChildrenArray<Item>,
    skin?: Skin,
};

@observer
export default class Items extends React.Component<Props> {
    @observable expandedWidth = 0;
    @observable showText = true;

    static defaultProps = {
        skin: 'light',
    };

    parentRef: { current: null | HTMLDivElement } = React.createRef();
    childRef: { current: null | HTMLUListElement } = React.createRef();

    componentDidMount() {
        this.setExpandedWidth();
        window.addEventListener('resize', this.setExpandedWidth);
    }

    componentDidUpdate() {
        this.setExpandedWidth();
    }

    @action setExpandedWidth = () => {
        if (this.childRef.current && this.childRef.current.offsetWidth) {
            const childWidth = this.childRef.current.offsetWidth;
            if (this.showText) {
                this.expandedWidth = childWidth;
            } else {
                if (childWidth > this.expandedWidth) {
                    this.expandedWidth = childWidth;
                }
            }
        }

        this.setShowText();
    };

    @action setShowText = () => {
        if (!this.parentRef.current || !this.parentRef.current.offsetWidth || !this.expandedWidth) {
            return;
        }

        const parentWidth = this.parentRef.current.offsetWidth;

        if (parentWidth >= this.expandedWidth) {
            this.showText = true;
        } else {
            this.showText = false;
        }
    };

    componentWillUnmount() {
        window.removeEventListener('resize', this.setExpandedWidth);
    }

    render() {
        const {
            skin,
            children,
        } = this.props;

        const itemsClass = classNames(
            itemsStyles.items,
            itemsStyles[skin]
        );

        return (
            <div className={itemsStyles.itemsWrapper} ref={this.parentRef}>
                <ul className={itemsClass} ref={this.childRef}>
                    {children && React.Children.map(children, (item, index) => {
                        return (
                            <li key={index}>
                                {React.cloneElement(item, {
                                    ...item.props,
                                    showText: this.showText,
                                    skin: skin,
                                })}
                            </li>
                        );
                    })}
                </ul>
            </div>
        );
    }
}
