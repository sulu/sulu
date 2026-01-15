// @flow
import React from 'react';
import {action, observable, toJS, reaction, computed} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import {arrayMove, translate, clipboard} from '../../utils';
import Button from '../Button';
import BlockToolbar from '../BlockToolbar';
import Icon from '../Icon';
import Sticky from '../Sticky';
import SortableBlockList from './SortableBlockList';
import blockCollectionStyles from './blockCollection.scss';
import type {RenderBlockContentCallback, BlockMode, Message} from './types';

type Props<T: string, U: {_id?: string, type: T, ...}> = {|
    addButtonText?: ?string,
    collapsable: boolean,
    defaultType: T,
    disabled: boolean,
    generateBlockIds?: (count: number) => Promise<Array<string>>,
    icons?: Array<Array<string>>,
    maxOccurs?: ?number,
    minOccurs?: ?number,
    movable: boolean,
    onChange: (value: Array<U>) => void,
    onDisplaySnackbar?: (message: Message) => void,
    onSettingsClick?: (index: number) => void,
    onSortEnd?: (oldIndex: number, newIndex: number) => void,
    pasteButtonText?: ?string,
    renderBlockContent: RenderBlockContentCallback<T, U>,
    types?: {[key: T]: string},
    value: Array<U>,
|};

const BLOCKS_CLIPBOARD_KEY = 'blocks';

@observer
class BlockCollection<T: string, U: {_id?: string, type: T, ...}> extends React.Component<Props<T, U>> {
    static idCounter = 0;

    static defaultProps = {
        collapsable: true,
        disabled: false,
        movable: true,
        value: [],
    };

    @observable pasteableBlocks: Array<U> = [];
    @observable generatedBlockIds: Array<number> = [];
    @observable expandedBlocks: Array<boolean> = [];
    @observable selectedBlocks: Array<boolean> = [];
    @observable mode: BlockMode = 'sortable';
    @observable isGeneratingIds: boolean = false;

    fillArraysDisposer: ?() => *;
    setPasteableBlocksDisposer: ?() => *;

    removeBlockIds = (block: any): any => {
        if (typeof block !== 'object' || block === null) {
            return block;
        }

        if (Array.isArray(block)) {
            return block.map((item) => this.removeBlockIds(item));
        }

        const cleanedBlock = {...block};

        // Remove _id from current level
        if ('_id' in cleanedBlock) {
            delete cleanedBlock._id;
        }

        // Recursively process nested objects
        Object.keys(cleanedBlock).forEach((key) => {
            if (typeof cleanedBlock[key] === 'object' && cleanedBlock[key] !== null) {
                cleanedBlock[key] = this.removeBlockIds(cleanedBlock[key]);
            }
        });

        return cleanedBlock;
    };

    constructor(props: Props<T, U>) {
        super(props);

        this.fillArraysDisposer = reaction(() => this.props.value.length, this.fillArrays, {fireImmediately: true});
        this.setPasteableBlocksDisposer = clipboard.observe(BLOCKS_CLIPBOARD_KEY, action((blocks) => {
            this.pasteableBlocks = blocks || [];
        }), true);

        if (props.movable === false) {
            this.mode = 'static';
        }

        // Ensure IDs are generated for initial value
        this.ensureBlockIds();
    }

    componentDidUpdate(prevProps: Props<T, U>) {
        const {generateBlockIds, value} = this.props;

        // Only call ensureBlockIds if:
        // 1. generateBlockIds function is provided
        // 2. The value reference changed
        // 3. There are actually blocks without IDs
        if (generateBlockIds && prevProps.value !== value) {
            const hasBlocksWithoutIds = value && value.some((block) => !block._id);
            if (hasBlocksWithoutIds) {
                this.ensureBlockIds();
            }
        }
    }

    componentWillUnmount() {
        this.fillArraysDisposer?.();
        this.setPasteableBlocksDisposer?.();
    }

    @action ensureBlockIds = async() => {
        const {generateBlockIds, onChange, value} = this.props;

        // Prevent multiple simultaneous ID generation operations
        if (this.isGeneratingIds || !value || !generateBlockIds) {
            return;
        }

        // Find all blocks without IDs
        const blocksWithoutIds = value.filter((block) => !block._id);

        if (blocksWithoutIds.length === 0) {
            return;
        }

        this.isGeneratingIds = true;

        try {
            // Generate IDs for all blocks without IDs
            const generatedIds = await generateBlockIds(blocksWithoutIds.length);

            // Ensure generated IDs is a valid array
            if (!generatedIds || !Array.isArray(generatedIds) || generatedIds.length !== blocksWithoutIds.length) {
                return;
            }

            // Update the value with the generated IDs
            let generatedIdIndex = 0;
            const updatedValue = value.map((block) => {
                if (!block._id) {
                    return {...block, _id: generatedIds[generatedIdIndex++]};
                }
                return block;
            });

            onChange(updatedValue);
        } finally {
            this.isGeneratingIds = false;
        }
    };

    fillArrays = () => {
        const {collapsable, defaultType, onChange, minOccurs, value} = this.props;
        const {expandedBlocks, generatedBlockIds, selectedBlocks} = this;

        if (!value) {
            return;
        }

        if (expandedBlocks.length > value.length) {
            expandedBlocks.splice(value.length);
        }

        if (selectedBlocks.length > value.length) {
            selectedBlocks.splice(value.length);
        }

        if (generatedBlockIds.length > value.length) {
            generatedBlockIds.splice(value.length);
        }

        const collapsed = collapsable ? false : true;

        expandedBlocks.push(...new Array(value.length - expandedBlocks.length).fill(collapsed));
        selectedBlocks.push(...new Array(value.length - selectedBlocks.length).fill(false));
        generatedBlockIds.push(
            ...new Array(value.length - generatedBlockIds.length).fill(false).map(() => ++BlockCollection.idCounter)
        );
        if (minOccurs && value.length < minOccurs) {
            expandedBlocks.push(...new Array(minOccurs - value.length).fill(true));
            selectedBlocks.push(...new Array(minOccurs - value.length).fill(false));
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

    @computed get selectedBlockIndexes(): Array<number> {
        const indexes = [];

        this.selectedBlocks.forEach((selected, index) => {
            if (selected) {
                indexes.push(index);
            }
        });

        return indexes;
    }

    @action handleAddBlock = async(insertionIndex: number) => {
        const {defaultType, generateBlockIds, onChange, value} = this.props;

        if (this.hasMaximumReached) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (value) {
            this.expandedBlocks.splice(insertionIndex, 0, true);
            this.selectedBlocks.splice(insertionIndex, 0, false);
            this.generatedBlockIds.splice(insertionIndex, 0, ++BlockCollection.idCounter);

            const elementsBefore = value.slice(0, insertionIndex);
            const elementsAfter = value.slice(insertionIndex);

            const newBlock: { _id?: string, type: string } = {type: defaultType};

            if (generateBlockIds) {
                const [newBlockId] = await generateBlockIds(1);
                newBlock._id = newBlockId;
            }

            // $FlowFixMe
            onChange([...elementsBefore, newBlock, ...elementsAfter]);
        }
    };

    handleAddButtonClick = (value: number) => {
        this.handleAddBlock(value);
    };

    handlePasteButtonClick = (value: number) => {
        this.handlePasteBlocks(value);
    };

    @action handlePasteBlocks = async(insertionIndex: number) => {
        const {generateBlockIds, onChange, onDisplaySnackbar, value} = this.props;

        if (this.hasMaximumReached) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (!value) {
            return;
        }

        this.expandedBlocks.splice(
            insertionIndex, 0, ...this.pasteableBlocks.map(() => true)
        );
        this.selectedBlocks.splice(
            insertionIndex, 0, ...this.pasteableBlocks.map(() => false)
        );
        this.generatedBlockIds.splice(
            insertionIndex, 0, ...this.pasteableBlocks.map(() => ++BlockCollection.idCounter)
        );

        // Generate IDs for all blocks being pasted (paste always gets new IDs)
        let generatedIds = [];
        if (generateBlockIds) {
            generatedIds = await generateBlockIds(this.pasteableBlocks.length);
        }

        const newElements = this.pasteableBlocks.map((block, index) => {
            // paste block with default type if type of block in clipboard is not known
            const newBlock = !this.props.types?.[block.type]
                ? {...block, type: this.props.defaultType}
                : {...block};

            // Paste always generates new IDs (remove old _id and assign new one)
            if (generateBlockIds) {
                newBlock._id = generatedIds[index];
            }

            return newBlock;
        });

        const elementsBefore = value.slice(0, insertionIndex);
        const elementsAfter = value.slice(insertionIndex);

        // $FlowFixMe
        onChange([...elementsBefore, ...newElements, ...elementsAfter]);
        clipboard.set(BLOCKS_CLIPBOARD_KEY, undefined);

        if (onDisplaySnackbar) {
            onDisplaySnackbar({
                type: 'info',
                text: translate('sulu_admin.%count%_blocks_pasted', {count: newElements.length}),
                icon: 'su-copy',
            });
        }
    };

    handleRemoveBlock = (index: number) => {
        this.removeBlocks([index]);
    };

    handleRemoveSelectedBlocks = () => {
        this.removeBlocks(this.selectedBlockIndexes);
    };

    @action removeBlocks = (indexes: Array<number>, shouldDisplaySnackbar: boolean = true) => {
        const {onChange, onDisplaySnackbar, movable, value} = this.props;

        if (!value) {
            return;
        }

        indexes.forEach(( index, count) => {
            if (this.hasMinimumReached) {
                // TODO throw snackbar message or maybe its not required as fillArrays already refill the array
                throw new Error('The minimum amount of blocks has already been reached!');
            }

            const currentRemoveIndex = index - count;

            this.expandedBlocks.splice(currentRemoveIndex, 1);
            this.selectedBlocks.splice(currentRemoveIndex, 1);
            this.generatedBlockIds.splice(currentRemoveIndex, 1);
        });

        if (this.generatedBlockIds.length < 2 && this.mode === 'selectable') {
            this.mode = movable ? 'sortable' : 'static';
        }

        onChange(value.filter((block, index) => indexes.indexOf(index) === -1));

        if (shouldDisplaySnackbar && onDisplaySnackbar) {
            onDisplaySnackbar({
                type: 'info',
                text: translate('sulu_admin.%count%_blocks_removed', {count: indexes.length}),
                icon: 'su-trash-alt',
            });
        }
    };

    handleDuplicateSelectedBlocks = () => {
        const {value} = this.props;

        this.duplicateBlocks(this.selectedBlockIndexes, value.length);
    };

    handleDuplicateBlock = (index: number) => {
        this.duplicateBlocks([index], index);
    };

    @action duplicateBlocks = async(indexes: Array<number>, insertAfterIndex: number) => {
        const {generateBlockIds, onChange, onDisplaySnackbar, value} = this.props;

        if (!value || indexes.length === 0) {
            return;
        }

        // Validate maximum limit before any operations
        if (value.length + indexes.length > (this.props.maxOccurs || Infinity)) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        const insertionIndex = insertAfterIndex + 1;

        // Update tracking arrays BEFORE async operations (must be in action context)
        this.expandedBlocks.splice(
            insertionIndex,
            0,
            ...indexes.map(() => true)
        );
        this.selectedBlocks.splice(
            insertionIndex,
            0,
            ...indexes.map(() => false)
        );
        this.generatedBlockIds.splice(
            insertionIndex,
            0,
            ...indexes.map(() => ++BlockCollection.idCounter)
        );

        // Generate all IDs upfront in a single batch request
        let generatedIds = [];
        if (generateBlockIds) {
            generatedIds = await generateBlockIds(indexes.length);
        }

        // Build all duplicated blocks from the ORIGINAL value array
        const duplicatedBlocks = indexes.map((sourceIndex, count) => {
            // Remove all _id fields (including nested ones) from the duplicated block
            const duplicatedBlock = generateBlockIds
                ? this.removeBlockIds(toJS(value[sourceIndex]))
                : {...toJS(value[sourceIndex])};

            // Assign new ID to top-level block
            if (generateBlockIds && generatedIds.length > count) {
                duplicatedBlock._id = generatedIds[count];
            }

            return duplicatedBlock;
        });

        const elementsBefore = value.slice(0, insertionIndex);
        const elementsAfter = value.slice(insertionIndex);
        onChange([...elementsBefore, ...duplicatedBlocks, ...elementsAfter]);

        if (onDisplaySnackbar) {
            onDisplaySnackbar({
                type: 'info',
                text: translate('sulu_admin.%count%_blocks_duplicated', {count: indexes.length}),
                icon: 'su-duplicate',
            });
        }
    };

    handleCopySelectedBlocks = () => {
        this.copyBlocks(this.selectedBlockIndexes);
    };

    handleCopyBlock = (index: number) => {
        this.copyBlocks([index]);
    };

    copyBlocks = (indexes: Array<number>, shouldDisplaySnackbar: boolean = true) => {
        const {generateBlockIds, onDisplaySnackbar, value} = this.props;

        if (!value) {
            return;
        }

        const blocks = [];

        indexes.forEach(( index) => {
            let block = toJS(value[index]);

            // Remove _id from copied blocks (including nested blocks) so new IDs are generated on paste
            if (generateBlockIds) {
                block = this.removeBlockIds(block);
            } else {
                block = {...block};
            }

            blocks.push(block);
        });

        clipboard.set(BLOCKS_CLIPBOARD_KEY, blocks);

        if (shouldDisplaySnackbar && onDisplaySnackbar) {
            onDisplaySnackbar({
                type: 'info',
                text: translate('sulu_admin.%count%_blocks_copied', {count: indexes.length}),
                icon: 'su-copy',
            });
        }
    };

    handleCutSelectedBlocks = () => {
        this.cutBlocks(this.selectedBlockIndexes);
    };

    handleCutBlock = (index: number) => {
        this.cutBlocks([index]);
    };

    cutBlocks = (indexes: Array<number>) => {
        const {generateBlockIds, onDisplaySnackbar, value} = this.props;

        if (!value) {
            return;
        }

        const blocks = [];

        indexes.forEach(( index) => {
            let block = toJS(value[index]);

            // Remove _id from cut blocks (including nested blocks) so new IDs are generated on paste
            if (generateBlockIds) {
                block = this.removeBlockIds(block);
            } else {
                block = {...block};
            }

            blocks.push(block);
        });

        clipboard.set(BLOCKS_CLIPBOARD_KEY, blocks);
        this.removeBlocks(indexes, false);

        if (onDisplaySnackbar) {
            onDisplaySnackbar({
                type: 'info',
                text: translate('sulu_admin.%count%_blocks_cut', {count: indexes.length}),
                icon: 'su-cut',
            });
        }
    };

    @action handleSortEnd = ({newIndex, oldIndex}: {newIndex: number, oldIndex: number}) => {
        const {onChange, onSortEnd, value} = this.props;

        this.expandedBlocks = arrayMove(this.expandedBlocks, oldIndex, newIndex);
        this.selectedBlocks = arrayMove(this.selectedBlocks, oldIndex, newIndex);
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

    @action handleSelect = (index: number) => {
        this.selectedBlocks[index] = true;
    };

    @action handleUnselect = (index: number) => {
        this.selectedBlocks[index] = false;
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

    @computed get hasMaximumReached() {
        const {maxOccurs, value} = this.props;

        return !!maxOccurs && value.length >= maxOccurs;
    }

    @computed get hasMinimumReached() {
        const {minOccurs, value} = this.props;

        return !!minOccurs && value.length <= minOccurs;
    }

    @computed get blockActions() {
        const blockActions = [];

        blockActions.push({
            type: 'button',
            icon: 'su-copy',
            label: translate('sulu_admin.copy'),
            onClick: this.handleCopyBlock,
        });

        if (!this.hasMinimumReached) {
            blockActions.push({
                type: 'button',
                icon: 'su-scissors',
                label: translate('sulu_admin.cut'),
                onClick: this.handleCutBlock,
            });
        }

        if (!this.hasMaximumReached) {
            blockActions.push({
                type: 'button',
                icon: 'su-duplicate',
                label: translate('sulu_admin.duplicate'),
                onClick: this.handleDuplicateBlock,
            });
        }

        if (!this.hasMinimumReached) {
            if (blockActions.length > 0) {
                blockActions.push({
                    type: 'divider',
                });
            }

            blockActions.push({
                type: 'button',
                icon: 'su-trash-alt',
                label: translate('sulu_admin.delete'),
                onClick: this.handleRemoveBlock,
            });
        }

        return blockActions;
    }

    renderAddButton = (aboveBlockIndex: number) => {
        const {addButtonText, pasteButtonText, disabled, value} = this.props;
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
                    disabled={disabled || this.hasMaximumReached}
                    icon="su-plus"
                    onClick={this.handleAddButtonClick}
                    skin="secondary"
                    value={aboveBlockIndex + 1}
                >
                    {addButtonText ? addButtonText : translate('sulu_admin.add_block')}
                </Button>
                {this.pasteableBlocks.length > 0 && (
                    <Button
                        className={blockCollectionStyles.addButton}
                        disabled={disabled || this.hasMaximumReached}
                        icon="su-copy"
                        onClick={this.handlePasteButtonClick}
                        skin="secondary"
                        value={aboveBlockIndex + 1}
                    >
                        {pasteButtonText
                            ? pasteButtonText
                            : translate('sulu_admin.paste_blocks', {count: this.pasteableBlocks.length})
                        }
                    </Button>
                )}
            </div>
        );
    };

    @action handleBlockToolbarCancel = () => {
        const {movable} = this.props;

        this.mode = movable ? 'sortable' : 'static';

        this.selectedBlocks.forEach((element, index) => {
            this.selectedBlocks[index] = false;
        });
    };

    @action handleClickSelectMultiple = () => {
        this.mode = 'selectable';
    };

    @action handleClickCollapseAll = () => {
        this.expandedBlocks.forEach((element, index) => {
            this.expandedBlocks[index] = false;
        });
    };

    @action handleClickExpandAll = () => {
        this.expandedBlocks.forEach((element, index) => {
            this.expandedBlocks[index] = true;
        });
    };

    @action handleBlockToolbarSelectAll = () => {
        this.selectedBlocks.forEach((element, index) => {
            this.selectedBlocks[index] = true;
        });
    };

    @action handleBlockToolbarUnselectAll = () => {
        this.selectedBlocks.forEach((element, index) => {
            this.selectedBlocks[index] = false;
        });
    };

    renderBlockToolbar = (isSticky: boolean) => {
        const {value} = this.props;
        const selectedBlocksCount = this.selectedBlocks.filter((element) => element).length;

        return (
            <BlockToolbar
                actions={[
                    {
                        label: translate('sulu_admin.copy'),
                        icon: 'su-copy',
                        handleClick: this.handleCopySelectedBlocks,
                    },
                    {
                        label: translate('sulu_admin.duplicate'),
                        icon: 'su-duplicate',
                        handleClick: this.handleDuplicateSelectedBlocks,
                    },
                    {
                        label: translate('sulu_admin.cut'),
                        icon: 'su-scissors',
                        handleClick: this.handleCutSelectedBlocks,
                    },
                    {
                        label: translate('sulu_admin.delete'),
                        icon: 'su-trash-alt',
                        handleClick: this.handleRemoveSelectedBlocks,
                    },
                ]}
                allSelected={selectedBlocksCount === value.length}
                mode={isSticky ? 'sticky' : 'static'}
                onCancel={this.handleBlockToolbarCancel}
                onSelectAll={this.handleBlockToolbarSelectAll}
                onUnselectAll={this.handleBlockToolbarUnselectAll}
                selectedCount={selectedBlocksCount}
            />
        );
    };

    renderBlockToolbarButton = () => {
        const allCollapsed = this.expandedBlocks.every((v) => !v);
        return (
            <div className={blockCollectionStyles.blockCollectionActionButtonContainer}>
                <button
                    className={blockCollectionStyles.blockCollectionActionButton}
                    onClick={this.handleClickSelectMultiple}
                    type="button"
                >
                    <Icon
                        aria-hidden={true}
                        className={blockCollectionStyles.blockCollectionActionButtonIcon}
                        name="su-check"
                    />
                    <span className={blockCollectionStyles.blockCollectionActionButtonText}>
                        {translate('sulu_admin.select_multiple_blocks')}
                    </span>
                </button>
                <button
                    className={blockCollectionStyles.blockCollectionActionButton}
                    onClick={allCollapsed ? this.handleClickExpandAll : this.handleClickCollapseAll}
                    type="button"
                >
                    <Icon
                        aria-hidden={true}
                        className={blockCollectionStyles.blockCollectionActionButtonIcon}
                        name={allCollapsed ? 'su-expand-vertical' : 'su-collapse-vertical'}
                    />
                    <span className={blockCollectionStyles.blockCollectionActionButtonText}>
                        {allCollapsed
                            ? translate('sulu_admin.expand_all_blocks')
                            : translate('sulu_admin.collapse_all_blocks')
                        }
                    </span>
                </button>
            </div>
        );
    };

    render() {
        const {
            collapsable,
            disabled,
            icons,
            onSettingsClick,
            renderBlockContent,
            types,
            value,
        } = this.props;

        return (
            <section className={blockCollectionStyles.blocks}>
                {
                    value.length > 1 ? (
                        this.mode === 'selectable'
                            ? <Sticky top={10}>
                                {this.renderBlockToolbar}
                            </Sticky>
                            : this.renderBlockToolbarButton()
                    ) : null
                }

                <div className={blockCollectionStyles.spacer} />

                <SortableBlockList
                    blockActions={this.blockActions}
                    disabled={disabled}
                    expandedBlocks={this.expandedBlocks}
                    generatedBlockIds={this.generatedBlockIds}
                    icons={icons}
                    lockAxis="y"
                    mode={this.mode}
                    onCollapse={collapsable ? this.handleCollapse : undefined}
                    onExpand={collapsable ? this.handleExpand : undefined}
                    onSelect={this.handleSelect}
                    onSettingsClick={onSettingsClick ? this.handleSettingsClick : undefined}
                    onSortEnd={this.handleSortEnd}
                    onTypeChange={this.handleTypeChange}
                    onUnselect={this.handleUnselect}
                    renderBlockContent={renderBlockContent}
                    renderDivider={this.renderAddButton}
                    selectedBlocks={this.selectedBlocks}
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
