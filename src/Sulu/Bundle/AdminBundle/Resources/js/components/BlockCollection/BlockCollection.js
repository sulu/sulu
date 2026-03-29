// @flow
import React from 'react';
import {action, observable, toJS, reaction, computed, runInAction} from 'mobx';
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

/**
 * Ensures that the value is an array. When switching templates, the value might be
 * an object {} instead of an array [], which would cause errors when calling array methods.
 */
function ensureArray<V>(value: mixed): Array<V> {
    if (Array.isArray(value)) {
        return value;
    }
    return [];
}

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
    @observable isFillingArrays: boolean = false;

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

        this.fillArraysDisposer = reaction(
            () => ensureArray(this.props.value).length,
            this.fillArrays,
            {fireImmediately: true}
        );
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
            const valueArray = ensureArray(value);
            const hasBlocksWithoutIds = valueArray.length > 0 && valueArray.some((block) => !block._id);
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
        const valueArray = ensureArray(value);

        // Prevent multiple simultaneous ID generation operations
        if (this.isGeneratingIds || valueArray.length === 0 || !generateBlockIds) {
            return;
        }

        // Find all blocks without IDs
        const blocksWithoutIds = valueArray.filter((block) => !block._id);

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
            const updatedValue = valueArray.map((block) => {
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

    @action fillArrays = async() => {
        const {collapsable, defaultType, generateBlockIds, minOccurs, onChange, value} = this.props;
        const {expandedBlocks, generatedBlockIds, selectedBlocks} = this;
        const valueArray = ensureArray(value);

        // Prevent concurrent executions
        if (this.isFillingArrays) {
            return;
        }

        this.isFillingArrays = true;

        try {
            if (expandedBlocks.length > valueArray.length) {
                expandedBlocks.splice(valueArray.length);
            }

            if (selectedBlocks.length > valueArray.length) {
                selectedBlocks.splice(valueArray.length);
            }

            if (generatedBlockIds.length > valueArray.length) {
                generatedBlockIds.splice(valueArray.length);
            }

            const collapsed = collapsable ? false : true;

            expandedBlocks.push(...new Array(valueArray.length - expandedBlocks.length).fill(collapsed));
            selectedBlocks.push(...new Array(valueArray.length - selectedBlocks.length).fill(false));
            generatedBlockIds.push(
                ...new Array(valueArray.length - generatedBlockIds.length).fill(false).map(() => ++BlockCollection.idCounter)
            );

            if (minOccurs && valueArray.length < minOccurs) {
                const newBlockCount = minOccurs - valueArray.length;

                // Create blocks and generate IDs if needed
                let newBlocks;
                if (generateBlockIds) {
                    try {
                        const blockIds = await generateBlockIds(newBlockCount);

                        if (!blockIds || !Array.isArray(blockIds) || blockIds.length !== newBlockCount) {
                            throw new Error(
                                `generateBlockIds must return an array with exactly ${newBlockCount} elements`
                            );
                        }

                        // Create blocks with IDs
                        newBlocks = Array.from(
                            {length: newBlockCount},
                            (_, index) => {
                                if (blockIds[index] === undefined || blockIds[index] === null) {
                                    throw new Error(`generateBlockIds returned an invalid ID at index ${index}`);
                                }
                                // $FlowFixMe
                                return {type: defaultType, _id: blockIds[index]};
                            }
                        );
                    } catch (error) {
                        // On error, don't add blocks to maintain consistent state
                        // Return early to prevent adding blocks with invalid or missing IDs
                        return;
                    }
                } else {
                    // Create blocks without IDs when generateBlockIds is not provided
                    newBlocks = Array.from(
                        {length: newBlockCount},
                        // $FlowFixMe
                        () => ({type: defaultType})
                    );
                }

                runInAction(() => {
                    expandedBlocks.push(...new Array(newBlockCount).fill(true));
                    selectedBlocks.push(...new Array(newBlockCount).fill(false));
                    generatedBlockIds.push(
                        ...new Array(newBlockCount).fill(false).map(() => ++BlockCollection.idCounter)
                    );

                    // $FlowFixMe
                    onChange([...valueArray, ...newBlocks]);
                });
            }
        } finally {
            this.isFillingArrays = false;
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
        const valueArray = ensureArray(value);

        if (this.hasMaximumReached) {
            throw new Error('The maximum amount of blocks has already been reached!');
        }

        if (valueArray.length > 0 || insertionIndex === 0) {
            const elementsBefore = valueArray.slice(0, insertionIndex);
            const elementsAfter = valueArray.slice(insertionIndex);

            // Create new block - field components will apply defaults in their constructors
            let newBlock;
            if (generateBlockIds) {
                const newBlockIds = await generateBlockIds(1);

                if (!newBlockIds || !Array.isArray(newBlockIds) || newBlockIds.length !== 1) {
                    throw new Error('generateBlockIds must return an array with exactly 1 element');
                }

                if (newBlockIds[0] === undefined || newBlockIds[0] === null) {
                    throw new Error('generateBlockIds returned an invalid ID');
                }

                newBlock = {type: defaultType, _id: newBlockIds[0]};
            } else {
                // Create block without ID when generateBlockIds is not provided
                newBlock = {type: defaultType};
            }

            runInAction(() => {
                this.expandedBlocks.splice(insertionIndex, 0, true);
                this.selectedBlocks.splice(insertionIndex, 0, false);
                this.generatedBlockIds.splice(insertionIndex, 0, ++BlockCollection.idCounter);

                // $FlowFixMe
                onChange([...elementsBefore, newBlock, ...elementsAfter]);
            });
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
        const valueArray = ensureArray(value);

        if (this.hasMaximumReached) {
            throw new Error('The maximum amount of blocks has already been reached!');
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

        const elementsBefore = valueArray.slice(0, insertionIndex);
        const elementsAfter = valueArray.slice(insertionIndex);

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
        const valueArray = ensureArray(value);

        if (valueArray.length === 0) {
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

        onChange(valueArray.filter((block, index) => indexes.indexOf(index) === -1));

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
        const valueArray = ensureArray(value);

        this.duplicateBlocks(this.selectedBlockIndexes, valueArray.length);
    };

    handleDuplicateBlock = (index: number) => {
        this.duplicateBlocks([index], index);
    };

    @action duplicateBlocks = async(indexes: Array<number>, insertAfterIndex: number) => {
        const {generateBlockIds, onChange, onDisplaySnackbar, value} = this.props;
        const valueArray = ensureArray(value);

        if (valueArray.length === 0 || indexes.length === 0) {
            return;
        }

        // Validate maximum limit before any operations
        if (valueArray.length + indexes.length > (this.props.maxOccurs || Infinity)) {
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
                ? this.removeBlockIds(toJS(valueArray[sourceIndex]))
                : {...toJS(valueArray[sourceIndex])};

            // Assign new ID to top-level block
            if (generateBlockIds && generatedIds.length > count) {
                duplicatedBlock._id = generatedIds[count];
            }

            return duplicatedBlock;
        });

        const elementsBefore = valueArray.slice(0, insertionIndex);
        const elementsAfter = valueArray.slice(insertionIndex);
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
        const valueArray = ensureArray(value);

        if (valueArray.length === 0) {
            return;
        }

        const blocks = [];

        indexes.forEach(( index) => {
            let block = toJS(valueArray[index]);

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
        const {generateBlockIds, onDisplaySnackbar, value} = this.props;       const valueArray = ensureArray(value);

        if (valueArray.length === 0) {
            return;
        }

        const blocks = [];

        indexes.forEach(( index) => {
            let block = toJS(valueArray[index]);

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
        const valueArray = ensureArray(value);

        return !!maxOccurs && valueArray.length >= maxOccurs;
    }

    @computed get hasMinimumReached() {
        const {minOccurs, value} = this.props;
        const valueArray = ensureArray(value);

        return !!minOccurs && valueArray.length <= minOccurs;
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
        const valueArray = ensureArray(value);
        const isDividerButton = aboveBlockIndex < valueArray.length - 1;

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
        const valueArray = ensureArray(value);
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
                allSelected={selectedBlocksCount === valueArray.length}
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
        const valueArray = ensureArray(value);

        return (
            <section className={blockCollectionStyles.blocks}>
                {
                    valueArray.length > 1 ? (
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
                    value={valueArray}
                />
                {this.renderAddButton(valueArray.length - 1)}
            </section>
        );
    }
}

export default BlockCollection;
