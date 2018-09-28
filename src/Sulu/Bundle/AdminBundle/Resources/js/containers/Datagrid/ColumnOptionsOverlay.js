// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import {SortableContainer, SortableElement} from 'react-sortable-hoc';
import {arrayMove, Overlay} from '../../components';
import type {Schema, SchemaEntry} from './types';
import ColumnOptionComponent from './ColumnOption';
import columnOptionsStyles from './columnOptions.scss';

type Props = {|
    onClose: () => void,
    onConfirm: (schema: Schema) => void,
    open: boolean,
    schema: Schema,
|};

type ColumnOption = {|
    schemaKey: string,
    schemaEntry: SchemaEntry,
|};

const SortableItem = SortableElement(ColumnOptionComponent);

const SortableList = SortableContainer(({children}) => {
    return (
        <div className={columnOptionsStyles.overlay}>
            {children}
        </div>
    );
});

@observer
export default class ColumnOptionsOverlay extends React.Component<Props> {
    @observable columnOptions: Array<ColumnOption> = [];

    handleConfirm = () => {
        const newSchema = {};
        for (const columnOption of this.columnOptions) {
            newSchema[columnOption.schemaKey] = columnOption.schemaEntry;
        }

        this.props.onConfirm(newSchema);
    };

    @action handleColumnOptionChange = (schemaKey: string, visibility: 'yes' | 'no') => {
        for (const columnOption of this.columnOptions) {
            if (columnOption.schemaKey === schemaKey) {
                columnOption.schemaEntry.visibility = visibility;

                return;
            }
        }
    };

    @action setColumnOptions = (schema: Schema) => {
        const columnOptions = [];
        Object.keys(schema).map((schemaKey) => {
            const schemaEntry = {...schema[schemaKey]};
            columnOptions.push({
                schemaKey: schemaKey,
                schemaEntry: schemaEntry,
            });
        });

        this.columnOptions = columnOptions;
    };

    @action componentDidMount() {
        this.setColumnOptions(this.props.schema);
    }

    @action componentDidUpdate(prevProps: Props) {
        const schema = this.props.schema;
        if (prevProps.schema !== schema) {
            this.setColumnOptions(schema);
        }
    }

    @action handleItemsSorted = ({oldIndex, newIndex}: {oldIndex: number, newIndex: number}) => {
        this.columnOptions = arrayMove(this.columnOptions, oldIndex, newIndex);
    };

    render() {
        const {
            onClose,
            open,
        } = this.props;

        return (
            <Overlay
                confirmText="Apply"
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size={'small'}
                title="Change schema amigo"
            >
                <SortableList
                    axis="y"
                    helperClass={columnOptionsStyles.dragging}
                    lockAxis="y"
                    lockToContainerEdges={true}
                    onSortEnd={this.handleItemsSorted}
                    useDragHandle={true}
                >
                    {this.columnOptions.map((columnOption, index) => {
                        if (columnOption.schemaEntry.visibility === 'never') {
                            return null;
                        }

                        return (
                            <SortableItem
                                index={index}
                                key={index}
                                label={columnOption.schemaEntry.label}
                                onChange={this.handleColumnOptionChange}
                                schemaKey={columnOption.schemaKey}
                                visibility={columnOption.schemaEntry.visibility}
                            />
                        );
                    })}
                </SortableList>
            </Overlay>
        );
    }
}
