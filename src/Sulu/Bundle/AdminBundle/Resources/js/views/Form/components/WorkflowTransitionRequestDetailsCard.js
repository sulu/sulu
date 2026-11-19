// @flow
import React from 'react';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import moment from 'moment';
import MemoryFormStoreFactory from '../../../containers/Form/stores/memoryFormStoreFactory';
import {translate} from '../../../utils/Translator';
import workflowCheckCardStyles from './workflowCheckCard.scss';
import detailsCardStyles from './workflowTransitionRequestDetailsCard.scss';
import type {Schema, SchemaEntry} from '../../../containers/Form/types';

export const REQUEST_FORM_KEY = 'workflow_transition_request_details';

const DATE_TIME_FORMATTER = new Intl.DateTimeFormat(undefined, {dateStyle: 'medium', timeStyle: 'short'});
const DATE_FORMATTER = new Intl.DateTimeFormat(undefined, {dateStyle: 'medium'});

type Props = {|
    data: {[string]: mixed},
|};

/**
 * The values the requester entered in the form of the request, as plain text. The reviewer reads them and
 * changes nothing, so no form is rendered. A field without a value is left out, and so is the whole card
 * when no field has one.
 */
@observer
class WorkflowTransitionRequestDetailsCard extends React.Component<Props> {
    formStore = MemoryFormStoreFactory.createFromFormKey(REQUEST_FORM_KEY);

    formatOptionTitle(entry: SchemaEntry, value: mixed): string {
        const values = entry.options?.values?.value;
        if (Array.isArray(values)) {
            const option = values.find((candidate) => String(candidate.name) === String(value));
            if (option) {
                return String(option.title ?? option.name);
            }
        }

        return String(value);
    }

    formatValue(entry: SchemaEntry, value: mixed): ?string {
        if (Array.isArray(value)) {
            const titles = value
                .filter((item) => typeof item === 'string' || typeof item === 'number')
                .map((item) => this.formatOptionTitle(entry, item));

            return titles.length > 0 ? titles.join(', ') : undefined;
        }

        if (typeof value === 'string') {
            if (value.trim() === '') {
                return undefined;
            }

            if (entry.type === 'date') {
                // A date has no time and no zone, so it is read as the local day. new Date() would read it as UTC.
                const date = moment(value, 'YYYY-MM-DD');

                return date.isValid() ? DATE_FORMATTER.format(date.toDate()) : value;
            }

            if (entry.type === 'datetime') {
                const date = new Date(value);

                return Number.isNaN(date.getTime()) ? value : DATE_TIME_FORMATTER.format(date);
            }

            return entry.type === 'single_select' ? this.formatOptionTitle(entry, value) : value;
        }

        if (typeof value === 'number') {
            return entry.type === 'single_select' ? this.formatOptionTitle(entry, value) : String(value);
        }

        if (typeof value === 'boolean') {
            return translate(value ? 'sulu_admin.yes' : 'sulu_admin.no');
        }

        return undefined;
    }

    collectRows(schema: Schema, rows: Array<{label: string, value: string}>) {
        Object.keys(schema).forEach((key) => {
            const entry = schema[key];
            // The form keeps what was typed into a field that is hidden again, and the reviewer must not read it.
            if (entry.visibleCondition && !jexl.evalSync(entry.visibleCondition, this.props.data)) {
                return;
            }

            // A section only groups fields. Their values sit at the top level of the data.
            if (entry.type === 'section') {
                if (entry.items) {
                    this.collectRows(entry.items, rows);
                }

                return;
            }

            const value = this.formatValue(entry, this.props.data[key]);
            if (value !== undefined && value !== null) {
                rows.push({label: entry.label || key, value});
            }
        });
    }

    getRows(): Array<{label: string, value: string}> {
        if (this.formStore.loading) {
            return [];
        }

        const rows = [];
        this.collectRows(this.formStore.schema, rows);

        return rows;
    }

    render() {
        const rows = this.getRows();
        if (rows.length === 0) {
            return null;
        }

        return (
            <div className={workflowCheckCardStyles.card}>
                <div className={workflowCheckCardStyles.header}>
                    <span className={workflowCheckCardStyles.headerText}>
                        {translate('sulu_content.workflow_transition_request.request_details')}
                    </span>
                </div>
                <dl className={detailsCardStyles.list}>
                    {rows.map((row) => (
                        <div className={detailsCardStyles.row} key={row.label}>
                            <dt className={detailsCardStyles.label}>{row.label}</dt>
                            <dd className={detailsCardStyles.value}>{row.value}</dd>
                        </div>
                    ))}
                </dl>
            </div>
        );
    }
}

export default WorkflowTransitionRequestDetailsCard;
