// @flow
import React from 'react';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import MemoryFormStoreFactory from '../../../containers/Form/stores/memoryFormStoreFactory';
import {translate} from '../../../utils/Translator';
import workflowCheckCardStyles from './workflowCheckCard.scss';
import detailsCardStyles from './workflowTransitionRequestDetailsCard.scss';
import type {SchemaEntry} from '../../../containers/Form/types';

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

    formatValue(entry: SchemaEntry, value: mixed): ?string {
        if (typeof value === 'string') {
            if (value.trim() === '') {
                return undefined;
            }

            if (entry.type === 'datetime' || entry.type === 'date') {
                const date = new Date(value);

                return Number.isNaN(date.getTime())
                    ? value
                    : (entry.type === 'date' ? DATE_FORMATTER : DATE_TIME_FORMATTER).format(date);
            }

            return value;
        }

        if (typeof value === 'number') {
            return String(value);
        }

        if (typeof value === 'boolean') {
            return translate(value ? 'sulu_admin.yes' : 'sulu_admin.no');
        }

        return undefined;
    }

    getRows(): Array<{label: string, value: string}> {
        if (this.formStore.loading) {
            return [];
        }

        const rows = [];
        Object.keys(this.formStore.schema).forEach((key) => {
            const entry = this.formStore.schema[key];
            // The form keeps what was typed into a field that is hidden again, and the reviewer must not read it.
            if (entry.visibleCondition && !jexl.evalSync(entry.visibleCondition, this.props.data)) {
                return;
            }

            const value = this.formatValue(entry, this.props.data[key]);
            if (value !== undefined && value !== null) {
                rows.push({label: entry.label || key, value});
            }
        });

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
