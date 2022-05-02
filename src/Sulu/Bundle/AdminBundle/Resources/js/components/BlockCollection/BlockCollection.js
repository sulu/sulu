// @flow
import React from 'react';
import {action, observable, toJS, reaction} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {arrayMove} from '../../utils';
import {translate} from '../../utils/Translator';
import Button from '../Button';
import SortableBlockList from './SortableBlockList';
import blockCollectionStyles from './blockCollection.scss';
import type {RenderBlockContentCallback} from './types';

type Props<T: string, U: {type: T}> = {|
    addButtonText?: ?string,
    collapsable: boolean,
    defaultType: T,
    disabled: boolean,
    icons?: Array<Array<string>>,
    maxOccurs?: ?number,
    minOccurs?: ?number,
    movable: boolean,
    onChange: (value: Array<U>) => void,
    onSettingsClick?: (index: number) => void,
    onSortEnd?: (oldIndex: number, newIndex: number) => void,
    renderBlockContent: RenderBlockContentCallback<T, U>,
    types?: {[key: T]: string},
    value: Array<U>,
|};

@observer
class BlockCollection<T: string, U: {type: T}> extends React.Component<Props<T, U>> {
    static idCounter = 0;

    static defaultProps = {
        collapsable: true,
        disabled: false,
        movable: true,
        value: [],
    };

    @observable generatedBlockIds: Array<number> = [];
    @observable expandedBlocks: Array<boolean> = [];

    constructor(props: Props<T, U>) {
        super(props);

        this.fillArrays();
        reaction(() => this.props.value.length, this.fillArrays);
    }

    fillArrays = () => {
        const {collapsable, defaultType, onChange, minOccurs, value} = this.props;
        const {expandedBlocks, generatedBlockIds} = this;

        if (!value) {
            return;
        }

        if (expandedBlocks.length > value.length) {
            expandedBlocks.splice(value.length);
        }

        if (generatedBlockIds.length > value.length) {
            generatedBlockIds.splice(value.length);
        }

        const collapsed = collapsable ? false : true;

        expandedBlocks.push(...new Array(value.length - expandedBlocks.length).fill(collapsed));
        generatedBlockIds.push(
            ...new Array(value.length - generatedBlockIds.length).fill(false).map(() => ++BlockCollection.idCounter)
        );
        if (minOccurs && value.length < minOccurs) {
            expandedBlocks.push(...new Array(minOccurs - value.length).fill(true));
            generatedBlockIds.push(
                ...new Array(minOccurs - value.length).fill(false).map(() => ++BlockCollection.idCounter)
            );

            onChange([
                ...value,
                ...Array.from(
                    {length: minOccurs - value.length},
                    // $FlowFixMe
                    () => ({type: defaultType})
                ),
            ]);
        }
    };

    @action handleAddBlock = (index: number) => {
        const {defaultType, onChange, value} = this.props;

        if (this.hasMaximumReached()) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.splice(index, 0, true);
            this.generatedBlockIds.splice(index, 0, ++BlockCollection.idCounter);

            // $FlowFixMe
            const elementsBefore = value.slice(0, index);
            const elementsAfter = value.slice(index);
            onChange([...elementsBefore, {type: defaultType}, ...elementsAfter]);
        }
    };

    @action handleRemoveBlock = (index: number) => {
        const {onChange, value} = this.props;

        if (this.hasMinimumReached()) {
            throw new Error('The minimum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.splice(index, 1);
            this.generatedBlockIds.splice(index, 1);
            onChange(value.filter((element, arrayIndex) => arrayIndex != index));
        }
    };

    @action handleSortEnd = ({newIndex, oldIndex}: {newIndex: number, oldIndex: number}) => {
        const {onChange, onSortEnd, value} = this.props;

        this.expandedBlocks = arrayMove(this.expandedBlocks, oldIndex, newIndex);
        this.generatedBlockIds = arrayMove(this.generatedBlockIds, oldIndex, newIndex);
        onChange(arrayMove(value, oldIndex, newIndex));

        if (onSortEnd) {
            onSortEnd(oldIndex, newIndex);
        }
    };

    @action handleCollapse = (index: number) => {
        this.expandedBlocks[index] = false;
    };

    @action handleExpand = (index: number) => {
        this.expandedBlocks[index] = true;
    };

    handleSettingsClick = (index: number) => {
        const {onSettingsClick} = this.props;

        if (onSettingsClick) {
            onSettingsClick(index);
        }
    };

    @action handleTypeChange: (type: T, index: number) => void = (type, index) => {
        const {onChange, value} = this.props;
        const newValue = toJS(value);
        newValue[index].type = type;
        onChange(newValue);
    };

    hasMaximumReached() {
        const {maxOccurs, value} = this.props;

        return !!maxOccurs && value.length >= maxOccurs;
    }

    hasMinimumReached() {
        const {minOccurs, value} = this.props;

        return !!minOccurs && value.length <= minOccurs;
    }

    renderAddButton = (aboveBlockIndex: number) => {
        const {addButtonText, disabled, value} = this.props;
        const isDividerButton = aboveBlockIndex < value.length - 1;

        const containerClass = classNames(
            blockCollectionStyles.addButtonContainer,
            {
                [blockCollectionStyles.addButtonDivider]: isDividerButton,
            }
        );

        return (
            <div className={containerClass}>
                <Button
                    className={blockCollectionStyles.addButton}
                    disabled={disabled || this.hasMaximumReached()}
                    icon="su-plus"
                    onClick={() => this.handleAddBlock(aboveBlockIndex + 1)}
                    skin="secondary"
                >
                    {addButtonText ? addButtonText : translate('sulu_admin.add_block')}
                </Button>
            </div>
        );
    };

    render() {
        const {
            collapsable,
            disabled,
            icons,
            movable,
            onSettingsClick,
            renderBlockContent,
            types,
            value,
        } = this.props;

        return (
            <section>
                <SortableBlockList
                    disabled={disabled}
                    expandedBlocks={this.expandedBlocks}
                    generatedBlockIds={this.generatedBlockIds}
                    icons={icons}
                    lockAxis="y"
                    movable={movable}
                    onCollapse={collapsable ? this.handleCollapse : undefined}
                    onExpand={collapsable ? this.handleExpand : undefined}
                    onRemove={this.hasMinimumReached() ? undefined : this.handleRemoveBlock}
                    onSettingsClick={onSettingsClick ? this.handleSettingsClick : undefined}
                    onSortEnd={this.handleSortEnd}
                    onTypeChange={this.handleTypeChange}
                    renderBlockContent={renderBlockContent}
                    renderDivider={this.renderAddButton}
                    types={types}
                    useDragHandle={true}
                    value={value}
                />
                {this.renderAddButton(value.length - 1)}
            </section>
        );
    }
}

export default BlockCollection;
