// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove} from 'react-sortable-hoc';
import {translate} from '../../utils/Translator';
import Button from '../Button';
import Icon from '../Icon';
import SortableBlockList from './SortableBlockList';
import blockCollectionStyles from './blockCollection.scss';
import type {RenderBlockContentCallback} from './types';

type Props = {
    onChange: (value: *) => void,
    renderBlockContent: RenderBlockContentCallback,
    types?: {[key: string]: string},
    value: Array<*>,
};

@observer
export default class BlockCollection extends React.Component<Props> {
    static defaultProps = {
        value: [],
    };

    @observable expandedBlocks: Array<boolean> = [];

    @observable blockTypes: Array<?string | number> = [];

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
        const {blockTypes, expandedBlocks} = this;

        if (!value) {
            return;
        }

        expandedBlocks.push(...new Array(value.length - expandedBlocks.length).fill(false));
        blockTypes.push(...new Array(value.length - blockTypes.length).fill(this.defaultType));
    }

    @action handleAddBlock = () => {
        const {onChange, value} = this.props;

        if (value) {
            this.expandedBlocks.push(false);
            this.blockTypes.push(this.defaultType);
            onChange([...value, {}]);
        }
    };

    @action handleRemove = (index: number) => {
        const {onChange, value} = this.props;

        if (value) {
            this.expandedBlocks.splice(index, 1);
            this.blockTypes.splice(index, 1);
            onChange(value.filter((element, arrayIndex) => arrayIndex != index));
        }
    };

    @action handleSortEnd = ({oldIndex, newIndex}: {oldIndex: number, newIndex: number}) => {
        const {onChange, value} = this.props;

        this.expandedBlocks = arrayMove(this.expandedBlocks, oldIndex, newIndex);
        this.blockTypes = arrayMove(this.blockTypes, oldIndex, newIndex);
        onChange(arrayMove(value, oldIndex, newIndex));
    };

    @action handleCollapse = (index: number) => {
        this.expandedBlocks[index] = false;
    };

    @action handleExpand = (index: number) => {
        this.expandedBlocks[index] = true;
    };

    @action handleTypeChange = (type: string | number, index: number) => {
        this.blockTypes[index] = type;
    };

    render() {
        const {renderBlockContent, types, value} = this.props;

        return (
            <section className={blockCollectionStyles.blockCollection}>
                <SortableBlockList
                    blockTypes={this.blockTypes}
                    expandedBlocks={this.expandedBlocks}
                    lockAxis="y"
                    onExpand={this.handleExpand}
                    onCollapse={this.handleCollapse}
                    onRemove={this.handleRemove}
                    onSortEnd={this.handleSortEnd}
                    onTypeChange={this.handleTypeChange}
                    renderBlockContent={renderBlockContent}
                    types={types}
                    useDragHandle={true}
                    value={value}
                />
                <Button skin="secondary" onClick={this.handleAddBlock}>
                    <Icon name="plus" className={blockCollectionStyles.addButtonIcon} />
                    {translate('sulu_admin.add_block')}
                </Button>
            </section>
        );
    }
}
