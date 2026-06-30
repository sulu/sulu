// @flow
import React, {Fragment} from 'react';
import {toJS} from 'mobx';
import {Button, Icon, Overlay} from 'sulu-admin-bundle/components';
import {translate} from '../../../utils/Translator';
import tableStyles from './table.scss';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

type Cell = {
    bold: boolean,
    italic: boolean,
    text: string,
    underline: boolean,
};

type TableValue = {
    body: Array<Array<mixed>>,
    head: Array<?string>,
    options?: Object,
    version?: number,
};

type NormalizedValue = {
    body: Array<Array<Cell>>,
    head: Array<string>,
};

type ActiveCell = {
    columnIndex: number,
    rowIndex: number,
};

type State = {
    activeCell: ?ActiveCell,
    fullscreen: boolean,
};

const FORMATS = [
    {key: 'bold', label: 'F', styleClass: tableStyles.bold, titleKey: 'sulu_admin.table.format_bold'},
    {key: 'italic', label: 'K', styleClass: tableStyles.italic, titleKey: 'sulu_admin.table.format_italic'},
    {key: 'underline', label: 'U', styleClass: tableStyles.underline, titleKey: 'sulu_admin.table.format_underline'},
];

function toCell(raw: mixed): Cell {
    if (raw && typeof raw === 'object') {
        return {
            text: raw.text == null ? '' : String(raw.text),
            bold: Boolean(raw.bold),
            italic: Boolean(raw.italic),
            underline: Boolean(raw.underline),
        };
    }

    return {text: raw == null ? '' : String(raw), bold: false, italic: false, underline: false};
}

function emptyCell(): Cell {
    return {text: '', bold: false, italic: false, underline: false};
}

export default class Table extends React.Component<FieldTypeProps<?TableValue>, State> {
    state = {
        activeCell: null,
        fullscreen: false,
    };

    get extras(): Object {
        const value = toJS(this.props.value);

        if (!value || typeof value !== 'object') {
            return {};
        }

        const {head, body, ...rest} = value; // eslint-disable-line no-unused-vars

        return rest;
    }

    get value(): NormalizedValue {
        const value = toJS(this.props.value);

        if (!value || !Array.isArray(value.head) || !Array.isArray(value.body)) {
            return {head: [], body: []};
        }

        return {
            head: value.head.map((cell) => (cell == null ? '' : String(cell))),
            body: value.body.map((row) => (Array.isArray(row) ? row.map(toCell) : [])),
        };
    }

    get columnCount(): number {
        const {head, body} = this.value;

        return Math.max(head.length, ...body.map((row) => row.length), 0);
    }

    normalize(value: NormalizedValue): NormalizedValue {
        const head = value.head.map((cell) => (cell == null ? '' : String(cell)));
        const body = value.body.map((row) => (Array.isArray(row) ? row.map(toCell) : []));
        const columns = Math.max(head.length, ...body.map((row) => row.length), 0);

        return {
            head: columns > 0 ? [...head, ...new Array(Math.max(columns - head.length, 0)).fill('')] : [],
            body: body.map((row) => [
                ...row,
                ...Array.from({length: Math.max(columns - row.length, 0)}, emptyCell),
            ]),
        };
    }

    emit(head: Array<string>, body: Array<Array<Cell>>): TableValue {
        return {...this.extras, head, body};
    }

    handleChange = (nextValue: NormalizedValue) => {
        const {onChange, onFinish} = this.props;
        const normalized = this.normalize(nextValue);

        this.setState({activeCell: null});

        onChange(this.emit(normalized.head, normalized.body));
        onFinish();
    };

    handleCellFocus = (rowIndex: number, columnIndex: number) => {
        this.setState({activeCell: {rowIndex, columnIndex}});
    };

    handleOpenFullscreen = () => {
        this.setState({fullscreen: true});
    };

    handleCloseFullscreen = () => {
        this.setState({fullscreen: false});
    };

    handleHeadChange = (columnIndex: number, event: SyntheticInputEvent<HTMLInputElement>) => {
        const {onChange} = this.props;
        const {head, body} = this.value;

        const nextHead = [...head];
        nextHead[columnIndex] = event.currentTarget.value;

        onChange(this.emit(nextHead, body));
    };

    handleCellChange = (
        rowIndex: number,
        columnIndex: number,
        event: SyntheticInputEvent<HTMLInputElement>
    ) => {
        const {onChange} = this.props;
        const {head, body} = this.value;
        const text = event.currentTarget.value;

        const nextBody = body.map((row, index) => {
            if (index !== rowIndex) {
                return row;
            }

            return row.map((cell, cellIndex) => (
                cellIndex === columnIndex ? {...cell, text} : cell
            ));
        });

        onChange(this.emit(head, nextBody));
    };

    handleToggleFormat = (rowIndex: number, columnIndex: number, key: string) => {
        const {onChange, onFinish} = this.props;
        const {head, body} = this.value;

        const nextBody = body.map((row, index) => {
            if (index !== rowIndex) {
                return row;
            }

            return row.map((cell, cellIndex) => (
                cellIndex === columnIndex ? {...cell, [key]: !cell[key]} : cell
            ));
        });

        onChange(this.emit(head, nextBody));
        onFinish();
    };

    handleBlur = () => {
        this.props.onFinish();
    };

    handleInsertColumn = (index: number) => {
        const {head, body} = this.value;

        const nextHead = [...head];
        nextHead.splice(index, 0, '');

        const nextBody = body.map((row) => {
            const nextRow = [...row];
            nextRow.splice(index, 0, emptyCell());

            return nextRow;
        });

        this.handleChange({head: nextHead, body: nextBody});
    };

    handleInsertRow = (index: number) => {
        const {head, body} = this.value;
        const columnCount = Math.max(this.columnCount, 1);

        const nextBody = [...body];
        nextBody.splice(index, 0, Array.from({length: columnCount}, emptyCell));

        this.handleChange({head, body: nextBody});
    };

    handleAddColumn = () => {
        this.handleInsertColumn(this.columnCount);
    };

    handleAddRow = () => {
        this.handleInsertRow(this.value.body.length);
    };

    handleRemoveColumn = (columnIndex: number) => {
        const {head, body} = this.value;

        this.handleChange({
            head: head.filter((cell, index) => index !== columnIndex),
            body: body.map((row) => row.filter((cell, index) => index !== columnIndex)),
        });
    };

    handleRemoveRow = (rowIndex: number) => {
        const {head, body} = this.value;

        this.handleChange({
            head,
            body: body.filter((row, index) => index !== rowIndex),
        });
    };

    renderColumnInsert(index: number, position: 'left' | 'right') {
        return (
            <span className={tableStyles.colInsert + ' ' + tableStyles[position]}>
                <span className={tableStyles.insertLineV} />
                <button
                    className={tableStyles.insertButton}
                    onClick={() => this.handleInsertColumn(index)}
                    tabIndex={-1}
                    title={translate('sulu_admin.table.insert_column')}
                    type="button"
                >
                    +
                </button>
            </span>
        );
    }

    renderRowInsert(index: number, position: 'top' | 'bottom') {
        return (
            <span className={tableStyles.rowInsert + ' ' + tableStyles[position]}>
                <span className={tableStyles.insertLineH} />
                <button
                    className={tableStyles.insertButton}
                    onClick={() => this.handleInsertRow(index)}
                    tabIndex={-1}
                    title={translate('sulu_admin.table.insert_row')}
                    type="button"
                >
                    +
                </button>
            </span>
        );
    }

    renderCell(cell: Cell, rowIndex: number, columnIndex: number) {
        const {disabled} = this.props;
        const {activeCell} = this.state;
        const isActive = Boolean(
            activeCell && activeCell.rowIndex === rowIndex && activeCell.columnIndex === columnIndex
        );

        const inputClassName = [tableStyles.input]
            .concat(cell.bold ? [tableStyles.bold] : [])
            .concat(cell.italic ? [tableStyles.italic] : [])
            .concat(cell.underline ? [tableStyles.underline] : [])
            .join(' ');

        return (
            <div className={tableStyles.bodyCell + (isActive ? ' ' + tableStyles.activeCell : '')}>
                <input
                    className={inputClassName}
                    disabled={disabled}
                    onBlur={this.handleBlur}
                    onChange={(event) => this.handleCellChange(rowIndex, columnIndex, event)}
                    onFocus={() => this.handleCellFocus(rowIndex, columnIndex)}
                    type="text"
                    value={cell.text}
                />
            </div>
        );
    }

    renderFormatWindow() {
        const {disabled} = this.props;
        const {activeCell, fullscreen} = this.state;
        const {body} = this.value;

        const cell = activeCell
            ? (body[activeCell.rowIndex] || [])[activeCell.columnIndex]
            : null;
        const hasActiveCell = Boolean(activeCell && cell);

        return (
            <div className={tableStyles.toolbarRow}>
                <div className={tableStyles.formatWindow}>
                    <div className={tableStyles.formatWindowButtons}>
                        {FORMATS.map((format) => (
                            <button
                                className={
                                    tableStyles.formatButton
                                    + (cell && cell[format.key] ? ' ' + tableStyles.active : '')
                                }
                                disabled={disabled || !hasActiveCell}
                                key={format.key}
                                onClick={() => activeCell
                                    && this.handleToggleFormat(activeCell.rowIndex, activeCell.columnIndex, format.key)}
                                onMouseDown={(event) => event.preventDefault()}
                                title={translate(format.titleKey)}
                                type="button"
                            >
                                <span className={format.styleClass}>{format.label}</span>
                            </button>
                        ))}
                    </div>
                </div>
                {!fullscreen &&
                    <div className={tableStyles.fullscreenWindow}>
                        <button
                            className={tableStyles.fullscreenButton}
                            onClick={this.handleOpenFullscreen}
                            title={translate('sulu_admin.table.fullscreen')}
                            type="button"
                        >
                            <Icon name="su-expand" />
                        </button>
                    </div>
                }
            </div>
        );
    }

    renderContent() {
        const {disabled} = this.props;
        const {head, body} = this.value;
        const columnCount = this.columnCount;
        const columns = Array.from({length: columnCount}, (value, index) => index);

        return (
            <Fragment>
                {!disabled && this.renderFormatWindow()}
                <div className={tableStyles.gridPanel}>
                <table className={tableStyles.grid}>
                    <thead>
                        <tr>
                            <th className={tableStyles.cornerCell} />
                            {columns.map((columnIndex) => (
                                <th className={tableStyles.headCell} key={columnIndex}>
                                    {!disabled && columnIndex === 0 && this.renderColumnInsert(0, 'left')}
                                    <div className={tableStyles.cellContent}>
                                        <input
                                            className={tableStyles.input}
                                            disabled={disabled}
                                            onBlur={this.handleBlur}
                                            onChange={(event) => this.handleHeadChange(columnIndex, event)}
                                            placeholder={translate('sulu_admin.table.column_title_placeholder')}
                                            type="text"
                                            value={head[columnIndex] || ''}
                                        />
                                        <Button
                                            disabled={disabled}
                                            icon="su-trash-alt"
                                            onClick={() => this.handleRemoveColumn(columnIndex)}
                                            skin="link"
                                        />
                                    </div>
                                    {!disabled && this.renderColumnInsert(columnIndex + 1, 'right')}
                                </th>
                            ))}
                            <th className={tableStyles.actionColumn} />
                        </tr>
                    </thead>
                    <tbody>
                        {body.map((row, rowIndex) => (
                            <tr key={rowIndex}>
                                <td className={tableStyles.gutterCell}>
                                    {!disabled && rowIndex === 0 && this.renderRowInsert(0, 'top')}
                                    {!disabled && this.renderRowInsert(rowIndex + 1, 'bottom')}
                                </td>
                                {columns.map((columnIndex) => (
                                    <td key={columnIndex}>
                                        {this.renderCell(row[columnIndex] || emptyCell(), rowIndex, columnIndex)}
                                    </td>
                                ))}
                                <td className={tableStyles.actionColumn}>
                                    <Button
                                        disabled={disabled}
                                        icon="su-trash-alt"
                                        onClick={() => this.handleRemoveRow(rowIndex)}
                                        skin="link"
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                </div>

                <div className={tableStyles.toolbar}>
                    <Button
                        disabled={disabled}
                        icon="su-plus"
                        onClick={this.handleAddRow}
                        skin="secondary"
                    >
                        {translate('sulu_admin.table.add_row')}
                    </Button>
                    <Button
                        disabled={disabled}
                        icon="su-plus"
                        onClick={this.handleAddColumn}
                        skin="secondary"
                    >
                        {translate('sulu_admin.table.add_column')}
                    </Button>
                </div>
            </Fragment>
        );
    }

    render() {
        const {fullscreen} = this.state;

        return (
            <div className={tableStyles.table}>
                {!fullscreen && this.renderContent()}
                <Overlay
                    confirmText={translate('sulu_admin.table.close')}
                    onClose={this.handleCloseFullscreen}
                    onConfirm={this.handleCloseFullscreen}
                    open={fullscreen}
                    size="large"
                    title={translate('sulu_admin.table.edit_overlay_title')}
                >
                    <div className={tableStyles.table + ' ' + tableStyles.overlayContent}>
                        {fullscreen && this.renderContent()}
                    </div>
                </Overlay>
            </div>
        );
    }
}
