// @flow
import React from 'react';
import {action, observable, toJS, reaction} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove} from '../../utils';
import {translate} from '../../utils/Translator';
import Button from '../Button';
import SortableBlockList from './SortableBlockList';
import blockCollectionStyles from './blockCollection.scss';
import type {BlockEntry, RenderBlockContentCallback} from './types';

type Props = {|
    addButtonText?: ?string,
    defaultType: string,
    disabled: boolean,
    icons?: Array<Array<string>>,
    maxOccurs?: ?number,
    minOccurs?: ?number,
    onChange: (value: Array<BlockEntry>) => void,
    onSettingsClick?: (index: number) => void,
    onSortEnd?: (oldIndex: number, newIndex: number) => void,
    renderBlockContent: RenderBlockContentCallback,
    types?: {[key: string]: string},
    value: Array<BlockEntry>,
|};

@observer
class BlockCollection extends React.Component<Props> {
    static idCounter = 0;

    static defaultProps = {
        disabled: false,
        value: [],
    };

    @observable generatedBlockIds: Array<number> = [];
    @observable expandedBlocks: Array<boolean> = [];

    constructor(props: Props) {
        super(props);

        this.fillArrays();
        reaction(() => this.props.value.length, this.fillArrays);
    }

    fillArrays = () => {
        const {defaultType, onChange, minOccurs, value} = this.props;
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

        expandedBlocks.push(...new Array(value.length - expandedBlocks.length).fill(false));
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
                    () => ({type: defaultType})
                ),
            ]);
        }
    };

    @action handleAddBlock = () => {
        const {defaultType, onChange, value} = this.props;

        if (this.hasMaximumReached()) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.push(true);
            this.generatedBlockIds.push(++BlockCollection.idCounter);

            onChange([...value, {type: defaultType}]);
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

    @action handleTypeChange = (type: string, index: number) => {
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

    render() {
        const {addButtonText, disabled, icons, onSettingsClick, renderBlockContent, types, value} = this.props;

        return (
            <section className={blockCollectionStyles.blockCollection}>
                <SortableBlockList
                    disabled={disabled}
                    expandedBlocks={this.expandedBlocks}
                    generatedBlockIds={this.generatedBlockIds}
                    icons={icons}
                    lockAxis="y"
                    onCollapse={this.handleCollapse}
                    onExpand={this.handleExpand}
                    onRemove={this.hasMinimumReached() ? undefined : this.handleRemoveBlock}
                    onSettingsClick={onSettingsClick ? this.handleSettingsClick : undefined}
                    onSortEnd={this.handleSortEnd}
                    onTypeChange={this.handleTypeChange}
                    renderBlockContent={renderBlockContent}
                    types={types}
                    useDragHandle={true}
                    value={value}
                />
                <Button
                    disabled={disabled || this.hasMaximumReached()}
                    icon="su-plus"
                    onClick={this.handleAddBlock}
                    skin="secondary"
                >
                    {addButtonText ? addButtonText : translate('sulu_admin.add_block')}
                </Button>
            </section>
        );
    }
}

export default BlockCollection;
