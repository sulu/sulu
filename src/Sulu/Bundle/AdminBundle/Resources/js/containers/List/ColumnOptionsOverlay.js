// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import {arrayMove, SortableContainer, SortableElement} from 'react-sortable-hoc';
import Overlay from '../../components/Overlay';
import {translate} from '../../utils';
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
    schemaEntry: SchemaEntry,
    schemaKey: string,
|};

const SortableItem = SortableElement(ColumnOptionComponent);

const SortableList = SortableContainer(({children, className}) => {
    return (
        <div className={className}>
            {children}
        </div>
    );
});

@observer
class ColumnOptionsOverlay extends React.Component<Props> {
    @observable columnOptions: Array<ColumnOption> = [];
    @observable sorting: boolean = false;

    handleConfirm = () => {
        const newSchema = {};
        for (const columnOption of this.columnOptions) {
            newSchema[columnOption.schemaKey] = columnOption.schemaEntry;
        }

        this.props.onConfirm(newSchema);
    };

    @action handleColumnOptionChange = (visibility: 'yes' | 'no', schemaKey: string) => {
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
                schemaKey,
                schemaEntry,
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

    @action handleItemsSortStart = () => {
        this.sorting = true;
    };

    @action handleItemsSortEnd = ({newIndex, oldIndex}: {newIndex: number, oldIndex: number}) => {
        this.columnOptions = arrayMove(this.columnOptions, oldIndex, newIndex);
        this.sorting = false;
    };

    render() {
        const {
            onClose,
            open,
        } = this.props;

        const className = classNames(
            columnOptionsStyles.overlay,
            {
                // TODO: This could be removed when following issue is fixed:
                // https://github.com/clauderic/react-sortable-hoc/issues/403
                [columnOptionsStyles.sorting]: this.sorting,
            }
        );

        return (
            <Overlay
                confirmText={translate('sulu_admin.confirm')}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="small"
                title={translate('sulu_admin.column_options')}
            >
                <SortableList
                    axis="y"
                    className={className}
                    helperClass={columnOptionsStyles.dragging}
                    lockAxis="y"
                    lockToContainerEdges={true}
                    onSortEnd={this.handleItemsSortEnd}
                    onSortStart={this.handleItemsSortStart}
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

export default ColumnOptionsOverlay;
