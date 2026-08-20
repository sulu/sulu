// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from '../../utils';
import Button from '../Button';
import Collapsible from '../Collapsible';
import Icon from '../Icon';
import collapsibleCollectionStyles from './collapsibleCollection.scss';
import type {ChildrenArray, Element} from 'react';

type Props = {|
    addButtonText?: ?string,
    children?: ChildrenArray<Element<typeof Collapsible> | false>,
    collapseAllText?: ?string,
    expandAllText?: ?string,
    onAddClick?: () => void,
|};

@observer
class CollapsibleCollection extends React.Component<Props> {
    // Keys of removed children linger here; every reader only tests membership against current children.
    @observable collapsedKeys: Array<string | number> = [];

    getChildKey(child: Element<typeof Collapsible>, index: number): string | number {
        return child.key !== null && child.key !== undefined ? child.key : index;
    }

    get childKeys(): Array<string | number> {
        const {children} = this.props;

        // $FlowFixMe
        return React.Children.map(children, (child, index) => {
            if (!child) {
                return null;
            }

            return this.getChildKey(child, index);
        }) || [];
    }

    get allCollapsed(): boolean {
        const childKeys = this.childKeys;

        return childKeys.length > 0 && childKeys.every((key) => this.collapsedKeys.includes(key));
    }

    @action handleCollapse = (key: string | number) => {
        if (!this.collapsedKeys.includes(key)) {
            this.collapsedKeys.push(key);
        }
    };

    @action handleExpand = (key: string | number) => {
        this.collapsedKeys = this.collapsedKeys.filter((collapsedKey) => collapsedKey !== key);
    };

    @action handleCollapseAllClick = () => {
        this.collapsedKeys = this.childKeys;
    };

    @action handleExpandAllClick = () => {
        this.collapsedKeys = [];
    };

    handleAddClick = () => {
        const {onAddClick} = this.props;

        if (onAddClick) {
            onAddClick();
        }
    };

    renderToggle = () => {
        const {collapseAllText, expandAllText} = this.props;
        const allCollapsed = this.allCollapsed;

        return (
            <div className={collapsibleCollectionStyles.toolbar}>
                <button
                    className={collapsibleCollectionStyles.toolbarButton}
                    onClick={allCollapsed ? this.handleExpandAllClick : this.handleCollapseAllClick}
                    type="button"
                >
                    <Icon
                        aria-hidden={true}
                        className={collapsibleCollectionStyles.toolbarButtonIcon}
                        name={allCollapsed ? 'su-expand-vertical' : 'su-collapse-vertical'}
                    />
                    <span className={collapsibleCollectionStyles.toolbarButtonText}>
                        {allCollapsed
                            ? (expandAllText ? expandAllText : translate('sulu_admin.expand_all'))
                            : (collapseAllText ? collapseAllText : translate('sulu_admin.collapse_all'))
                        }
                    </span>
                </button>
            </div>
        );
    };

    render() {
        const {addButtonText, children, onAddClick} = this.props;

        return (
            <section className={collapsibleCollectionStyles.collapsibleCollection}>
                {this.childKeys.length > 0 && this.renderToggle()}
                <div className={collapsibleCollectionStyles.items}>
                    {/* $FlowFixMe */}
                    {React.Children.map(children, (child, index) => {
                        if (!child) {
                            return null;
                        }

                        const key = this.getChildKey(child, index);

                        // $FlowFixMe
                        return React.cloneElement(child, {
                            expanded: !this.collapsedKeys.includes(key),
                            onCollapse: () => this.handleCollapse(key),
                            onExpand: () => this.handleExpand(key),
                        });
                    })}
                </div>
                {onAddClick &&
                    <div className={collapsibleCollectionStyles.addButtonContainer}>
                        <Button icon="su-plus" onClick={this.handleAddClick} skin="secondary">
                            {addButtonText ? addButtonText : translate('sulu_admin.add')}
                        </Button>
                    </div>
                }
            </section>
        );
    }
}

export default CollapsibleCollection;
