// @flow
import React from 'react';
import {action, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove} from 'react-sortable-hoc';
import {translate} from '../../utils/Translator';
import Button from '../Button';
import Icon from '../Icon';
import SortableBlocks from './SortableBlocks';
import blockCollectionStyles from './blockCollection.scss';
import type {BlockEntry, RenderBlockContentCallback} from './types';

type Props = {
    maxOccurs?: number,
    minOccurs?: number,
    onChange: (value: Array<BlockEntry>) => void,
    renderBlockContent: RenderBlockContentCallback,
    types?: {[key: string]: string},
    value: Array<BlockEntry>,
};

@observer
export default class BlockCollection extends React.Component<Props> {
    static idCounter = 0;

    static defaultProps = {
        value: [],
    };

    @observable expandedBlocks: Array<boolean> = [];

    @computed get defaultType(): ?string {
        const {types} = this.props;

        if (!types) {
            return undefined;
        }

        return Object.keys(types)[0];
    }

    componentWillMount() {
        this.fillArrays();
    }

    fillArrays() {
        const {value} = this.props;
        const {expandedBlocks} = this;

        if (!value) {
            return;
        }

        expandedBlocks.push(...new Array(value.length - expandedBlocks.length).fill(false));
    }

    @action handleAddBlock = () => {
        const {maxOccurs, onChange, value} = this.props;

        if (maxOccurs && value.length >= maxOccurs) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.push(true);

            const newBlock = this.defaultType ? {type: this.defaultType} : {};
            onChange([...value, newBlock]);
        }
    };

    @action handleRemoveBlock = (index: number) => {
        const {minOccurs, onChange, value} = this.props;

        if (minOccurs && value.length <= minOccurs) {
            throw new Error('The minimum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.splice(index, 1);
            onChange(value.filter((element, arrayIndex) => arrayIndex != index));
        }
    };

    @action handleSortEnd = ({oldIndex, newIndex}: {oldIndex: number, newIndex: number}) => {
        const {onChange, value} = this.props;

        this.expandedBlocks = arrayMove(this.expandedBlocks, oldIndex, newIndex);
        onChange(arrayMove(value, oldIndex, newIndex));
    };

    @action handleCollapse = (index: number) => {
        this.expandedBlocks[index] = false;
    };

    @action handleExpand = (index: number) => {
        this.expandedBlocks[index] = true;
    };

    @action handleTypeChange = (type: string | number, index: number) => {
        const {onChange, value} = this.props;
        const newValue = toJS(value);
        newValue[index].type = type;
        onChange(newValue);
    };

    render() {
        const {renderBlockContent, types, value} = this.props;

        const identifiedValues = value.map((block) => {
            if (!block.__id) {
                block.__id = ++BlockCollection.idCounter;
            }

            return block;
        });

        return (
            <section className={blockCollectionStyles.blockCollection}>
                <SortableBlocks
                    expandedBlocks={this.expandedBlocks}
                    lockAxis="y"
                    onExpand={this.handleExpand}
                    onCollapse={this.handleCollapse}
                    onRemove={this.handleRemoveBlock}
                    onSortEnd={this.handleSortEnd}
                    onTypeChange={this.handleTypeChange}
                    renderBlockContent={renderBlockContent}
                    types={types}
                    useDragHandle={true}
                    value={identifiedValues}
                />
                <Button skin="secondary" onClick={this.handleAddBlock}>
                    <Icon name="su-plus" className={blockCollectionStyles.addButtonIcon} />
                    {translate('sulu_admin.add_block')}
                </Button>
            </section>
        );
    }
}
