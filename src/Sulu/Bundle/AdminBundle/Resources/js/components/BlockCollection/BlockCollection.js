// @flow
import React from 'react';
import {action, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove} from 'react-sortable-hoc';
import {translate} from '../../utils/Translator';
import Button from '../Button';
import SortableBlockList from './SortableBlockList';
import blockCollectionStyles from './blockCollection.scss';
import type {BlockEntry, RenderBlockContentCallback} from './types';

type Props = {|
    defaultType: string,
    disabled: boolean,
    maxOccurs?: ?number,
    minOccurs?: ?number,
    onChange: (value: Array<BlockEntry>) => void,
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

    @observable expandedBlocks: Array<boolean> = [];

    constructor(props: Props) {
        super(props);

        this.fillArrays();
    }

    fillArrays() {
        const {defaultType, onChange, minOccurs, value} = this.props;
        const {expandedBlocks} = this;

        if (!value) {
            return;
        }

        expandedBlocks.push(...new Array(value.length - expandedBlocks.length).fill(false));
        if (minOccurs && value.length < minOccurs) {
            expandedBlocks.push(...new Array(minOccurs - value.length).fill(true));
            onChange([
                ...value,
                ...Array.from(
                    {length: minOccurs - value.length},
                    () => ({type: defaultType})
                ),
            ]);
        }
    }

    @action handleAddBlock = () => {
        const {defaultType, onChange, value} = this.props;

        if (this.hasMaximumReached()) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.push(true);

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
            onChange(value.filter((element, arrayIndex) => arrayIndex != index));
        }
    };

    @action handleSortEnd = ({oldIndex, newIndex}: {oldIndex: number, newIndex: number}) => {
        const {onChange, onSortEnd, value} = this.props;

        this.expandedBlocks = arrayMove(this.expandedBlocks, oldIndex, newIndex);
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
        const {disabled, renderBlockContent, types, value} = this.props;

        const identifiedValues = value.map((block) => {
            if (!block.__id) {
                block.__id = ++BlockCollection.idCounter;
            }

            return block;
        });

        return (
            <section className={blockCollectionStyles.blockCollection}>
                <SortableBlockList
                    disabled={disabled}
                    expandedBlocks={this.expandedBlocks}
                    lockAxis="y"
                    onCollapse={this.handleCollapse}
                    onExpand={this.handleExpand}
                    onRemove={this.hasMinimumReached() ? undefined : this.handleRemoveBlock}
                    onSortEnd={this.handleSortEnd}
                    onTypeChange={this.handleTypeChange}
                    renderBlockContent={renderBlockContent}
                    types={types}
                    useDragHandle={true}
                    value={identifiedValues}
                />
                <Button
                    disabled={disabled || this.hasMaximumReached()}
                    icon="su-plus"
                    onClick={this.handleAddBlock}
                    skin="secondary"
                >
                    {translate('sulu_admin.add_block')}
                </Button>
            </section>
        );
    }
}

export default BlockCollection;
